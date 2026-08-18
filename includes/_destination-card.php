<?php
/**
 * One destination card. Expects $dest (a destinations row, optionally with
 * region_name and package_count) and $ci as a running index for the stagger.
 * Included from destinations.php for both top-level and sub-destinations.
 */
$fallbackImg = 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=500&q=75';
?>
<a href="<?= url('destinations.php?slug=' . h($dest['slug'])) ?>" class="destination-card"
   data-animate data-delay="<?= (($ci ?? 0) % 4) * 80 ?>">
  <img src="<?= h($dest['hero_image'] ?: $fallbackImg) ?>" alt="<?= h($dest['name']) ?>"
       loading="lazy" decoding="async">
  <div class="destination-card-info">
    <div class="destination-card-country"><?= h($dest['group_label'] ?? $dest['region_name'] ?? $dest['country'] ?? '') ?></div>
    <div class="destination-card-name"><?= h($dest['name']) ?></div>
    <div class="destination-card-count">
      <?= (int)($dest['package_count'] ?? 0) ?> package<?= (int)($dest['package_count'] ?? 0) != 1 ? 's' : '' ?> available
    </div>
  </div>
</a>
