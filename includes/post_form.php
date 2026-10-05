<?php
/**
 * Shared create/edit form for posts.
 * Expects: $formAction, $submitLabel, $old ['title','body'], $errors, optional $cancelUrl.
 */
?>
<form method="post" action="<?= e($formAction) ?>" class="js-validate" novalidate>
    <?= csrf_field() ?>

    <label for="title">Title</label>
    <input type="text" id="title" name="title" value="<?= e($old['title']) ?>"
           required minlength="<?= TITLE_MIN ?>" maxlength="<?= TITLE_MAX ?>">
    <?= field_error($errors, 'title') ?>

    <label for="body">Body</label>
    <textarea id="body" name="body" rows="10"
              required minlength="<?= POST_BODY_MIN ?>" maxlength="<?= POST_BODY_MAX ?>"
              data-counter><?= e($old['body']) ?></textarea>
    <?= field_error($errors, 'body') ?>

    <div class="actions">
        <button type="submit" class="btn"><?= e($submitLabel) ?></button>
        <a href="<?= e($cancelUrl ?? 'index.php') ?>" class="btn btn-ghost">Cancel</a>
    </div>
</form>
