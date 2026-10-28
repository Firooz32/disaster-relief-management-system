<?php
/** Live weather view provided by Ventusky. */
declare(strict_types=1);
require __DIR__ . '/inc/boot.php';
guard();

page_start('Live Weather', 'weather');
?>
<section class="panel">
  <h2>Live Weather for Khulna</h2>
  <p class="muted">Explore current conditions, forecasts and interactive weather layers. Weather data and map controls are provided by Ventusky; use its location search to change location.</p>
  <p class="weather-fallback"><a class="btn btn-primary" href="https://www.ventusky.com/khulna" target="_blank" rel="noopener noreferrer">Open Live Weather in a new tab</a></p>
  <iframe class="weather-frame" style="display:block;width:100%;height:85vh;min-height:620px;max-height:1100px" src="https://www.ventusky.com/khulna" title="Ventusky live weather map and forecast for Khulna" loading="eager" referrerpolicy="strict-origin-when-cross-origin"></iframe>
</section>
<?php page_end(); ?>
