<?php
declare(strict_types=1);
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/stats.php';
guard();

$formError = '';
$values = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!can_edit()) {
        flash('err', 'Only an editor or administrator can receive stock.');
        header('Location: stock.php');
        exit;
    }
    if (!csrf_ok()) {
        flash('err', 'Session expired - please try again.');
        header('Location: stock.php');
        exit;
    }
    foreach (['Relief_ID', 'Batch_Code', 'Quantity_Received', 'Received_At', 'Expiry_Date', 'Source', 'Storage_Location', 'Notes'] as $field) {
        $value = $_POST[$field] ?? '';
        $values[$field] = is_string($value) ? trim($value) : '';
    }
    $itemId = filter_var($values['Relief_ID'], FILTER_VALIDATE_INT);
    $quantity = filter_var($values['Quantity_Received'], FILTER_VALIDATE_INT);
    $receivedAt = DateTimeImmutable::createFromFormat('!Y-m-d', $values['Received_At']);
    $receivedErrors = DateTimeImmutable::getLastErrors();
    $expiry = $values['Expiry_Date'] === '' ? null : DateTimeImmutable::createFromFormat('!Y-m-d', $values['Expiry_Date']);
    $dateErrors = DateTimeImmutable::getLastErrors();
    $validReceivedDate = $receivedAt !== false && ($receivedErrors === false || ($receivedErrors['warning_count'] === 0 && $receivedErrors['error_count'] === 0));
    $expiryErrors = $dateErrors;
    $validExpiryDate = $expiry === null || ($expiry !== false && ($expiryErrors === false || ($expiryErrors['warning_count'] === 0 && $expiryErrors['error_count'] === 0)));
    if ($itemId === false || $itemId < 1 || $quantity === false || $quantity < 1) {
        $formError = 'Choose a relief item and enter a positive whole-number quantity.';
    } elseif ($values['Batch_Code'] === '' || strlen($values['Batch_Code']) > 80) {
        $formError = 'Enter a batch code of 1 to 80 characters.';
    } elseif (!$validReceivedDate || !$validExpiryDate) {
        $formError = 'Enter valid received and expiry dates.';
    } elseif ($expiry instanceof DateTimeImmutable && $receivedAt instanceof DateTimeImmutable && $expiry < $receivedAt) {
        $formError = 'The expiry date cannot be before the received date.';
    } else {
        try {
            db()->begin_transaction();
            $item = row1('SELECT Item_Name, Quantity FROM relief_item WHERE Relief_ID = ? FOR UPDATE', [$itemId]);
            if ($item === null) {
                throw new RuntimeException('That relief item no longer exists.');
            }
            if ((int) $item['Quantity'] > 2147483647 - $quantity) {
                throw new RuntimeException('That receipt would exceed the supported stock quantity.');
            }
            q('INSERT INTO relief_batch (Relief_ID, Batch_Code, Quantity_Received, Received_At, Expiry_Date, Source, Storage_Location, Notes, Received_By)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$itemId, $values['Batch_Code'], $quantity, $receivedAt->format('Y-m-d'), $expiry?->format('Y-m-d'),
                 $values['Source'] ?: null, $values['Storage_Location'] ?: null, $values['Notes'] ?: null, (int) $_SESSION['uid']]);
            $batchId = (int) db()->insert_id;
            q('UPDATE relief_item SET Quantity = Quantity + ? WHERE Relief_ID = ?', [$quantity, $itemId]);
            audit('add', 'relief_batch', 'Batch_ID=' . $batchId,
                'Received ' . $quantity . ' units of ' . $item['Item_Name'] . ' (' . $values['Batch_Code'] . ').');
            db()->commit();
            flash('ok', 'Stock receipt recorded; batch inventory and item totals were updated.');
            header('Location: stock.php#batch-' . $batchId);
            exit;
        } catch (mysqli_sql_exception $e) {
            db()->rollback();
            error_log('[DRMS] Stock receipt failed: ' . $e->getMessage());
            $formError = $e->getCode() === 1062 ? 'That batch code is already in use.' : 'The receipt could not be saved. Check the values and try again.';
        } catch (RuntimeException $e) {
            db()->rollback();
            $formError = $e->getMessage();
        }
    }
}

$items = relief_summary();
$batches = stock_batches();
$movements = stock_movements(100);
page_start('Inventory & stock', 'stock');
?>
<section class="panel">
  <h2>Stock position</h2>
  <p class="muted">Received quantities are maintained from receipt batches. Dispatches are linked to a batch, and available stock is calculated from receipts minus dispatches.</p>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>Relief item</th><th class="r">Total received</th><th class="r">Dispatched</th><th class="r">Available</th><th class="r">Batch balance</th></tr></thead>
    <tbody>
      <?php foreach ($items as $item):
          $batchBalance = array_sum(array_map(fn($b) => (int) $b['Remaining'], array_filter($batches, fn($b) => (int) $b['Relief_ID'] === (int) $item['id']))); ?>
        <tr><td><?= h($item['name']) ?></td><td class="r"><?= number_format((int) $item['received']) ?></td><td class="r"><?= number_format((int) $item['distributed']) ?></td>
          <td class="r"><?= number_format((int) $item['remaining']) ?></td><td class="r"><?= number_format($batchBalance) ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$items): ?><tr><td colspan="5" class="muted center">No relief stock has been recorded.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</section>

<?php if (can_edit()): ?>
<section class="panel narrow">
  <h2>Receive a stock batch</h2>
  <p class="muted small">Use a unique batch code for each receipt. Existing stock was retained as a legacy batch; its original receipt date is unknown.</p>
  <?php if ($formError !== ''): ?><div class="alert alert-err"><?= h($formError) ?></div><?php endif; ?>
  <form method="post" action="stock.php">
    <input type="hidden" name="csrf" value="<?= h(csrf()) ?>">
    <label class="fld"><span>Relief item <em>*</em></span>
      <select name="Relief_ID" required>
        <option value="">- select item -</option>
        <?php foreach ($items as $item): ?><option value="<?= (int) $item['id'] ?>"<?= (string) $item['id'] === ($values['Relief_ID'] ?? '') ? ' selected' : '' ?>><?= h($item['name']) ?></option><?php endforeach; ?>
      </select>
    </label>
    <label class="fld"><span>Batch code <em>*</em></span><input name="Batch_Code" maxlength="80" required value="<?= h($values['Batch_Code'] ?? '') ?>"></label>
    <label class="fld"><span>Quantity received <em>*</em></span><input type="number" name="Quantity_Received" min="1" step="1" required value="<?= h($values['Quantity_Received'] ?? '') ?>"></label>
    <label class="fld"><span>Received date <em>*</em></span><input type="date" name="Received_At" required value="<?= h($values['Received_At'] ?? date('Y-m-d')) ?>"></label>
    <label class="fld"><span>Expiry date</span><input type="date" name="Expiry_Date" value="<?= h($values['Expiry_Date'] ?? '') ?>"></label>
    <label class="fld"><span>Supplier / source</span><input name="Source" maxlength="160" value="<?= h($values['Source'] ?? '') ?>"></label>
    <label class="fld"><span>Storage location</span><input name="Storage_Location" maxlength="160" value="<?= h($values['Storage_Location'] ?? '') ?>"></label>
    <label class="fld"><span>Notes</span><textarea name="Notes" rows="3"><?= h($values['Notes'] ?? '') ?></textarea></label>
    <div class="actions"><button class="btn btn-primary" type="submit">Record receipt</button></div>
  </form>
</section>
<?php endif; ?>

<section class="panel">
  <h2>Stock batches <span class="muted small">(<?= count($batches) ?>)</span></h2>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>Batch</th><th>Item</th><th>Received</th><th>Expiry</th><th class="r">Received qty</th><th class="r">Dispatched</th><th class="r">Available</th><th>Source / storage</th><th>Recorded by</th></tr></thead>
    <tbody>
      <?php foreach ($batches as $batch): ?>
        <tr id="batch-<?= (int) $batch['Batch_ID'] ?>"><td><b><?= h($batch['Batch_Code']) ?></b></td><td><?= h($batch['Item_Name']) ?></td>
          <td><?= h($batch['Received_At'] ?? 'Unknown (legacy)') ?></td><td><?= h($batch['Expiry_Date'] ?? 'Not recorded') ?></td>
          <td class="r"><?= number_format((int) $batch['Quantity_Received']) ?></td><td class="r"><?= number_format((int) $batch['Dispatched']) ?></td>
          <td class="r"><?= number_format((int) $batch['Remaining']) ?></td>
          <td><?= h(implode(' / ', array_filter([$batch['Source'], $batch['Storage_Location']])) ?: '-') ?></td><td><?= h($batch['Received_By'] ?? 'Not recorded') ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$batches): ?><tr><td colspan="9" class="muted center">No stock batches recorded.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</section>

<section class="panel">
  <h2>Recent stock movements</h2>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>Date</th><th>Movement</th><th>Relief item</th><th>Batch</th><th class="r">Quantity</th><th>Supplier / destination</th></tr></thead>
    <tbody>
      <?php foreach ($movements as $movement): ?>
        <tr><td><?= h($movement['Movement_Date'] ?? 'Unknown (legacy)') ?></td><td><?= h($movement['Movement_Type']) ?></td>
          <td><?= h($movement['Item_Name']) ?></td><td><?= h($movement['Batch_Code']) ?></td>
          <td class="r"><?= number_format((int) $movement['Quantity']) ?></td><td><?= h($movement['Destination'] ?? '-') ?></td></tr>
      <?php endforeach; ?>
      <?php if (!$movements): ?><tr><td colspan="6" class="muted center">No stock movements recorded.</td></tr><?php endif; ?>
    </tbody>
  </table></div>
</section>
<?php page_end(); ?>
