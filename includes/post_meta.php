<?php
/** Byline for a post. Expects $post with author, created_at, updated_at. */
?>
<p class="meta">
    By <strong><?= e($post['author']) ?></strong>
    · <time datetime="<?= e($post['created_at']) ?>"><?= e(format_datetime($post['created_at'])) ?></time>
    <?php if ($post['updated_at'] !== null): ?>
        <span class="badge" title="Edited <?= e(format_datetime($post['updated_at'])) ?>">Edited</span>
    <?php endif; ?>
</p>
