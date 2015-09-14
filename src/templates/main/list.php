<?php
assert('!$path->isNone()');
$totalSize = 0;
?>
<div class="panel panel-default">
    <table class="table table-condensed table-hover">
        <thead>
            <tr>
                <th><?= _('Name') ?></th>
                <th><?= _('Type') ?></th>
                <th><?= _('Size') ?></th>
                <th><?= _('Modified') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if(!$path->isFavoriteRoot()): ?>
            <tr>
                <td colspan="4"><a href="./../">..</a></td>
            </tr>
            <?php endif; ?>
            <?php foreach ($files as $file): ?>
            <?php $totalSize += $file->getSize() ?>
            <tr>
                <td><?= file_to_link($file, $path) ?></td>
                <td><?= e(_($file->getType())) ?></td>
                <td><?= e(bytes_to_human_readable($file->getSize())) ?></td>
                <td><?= e(format_date($file->getMTime())) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="panel-body table-footer">
        <p>
            <?php eprintf(
                _('%d items, %s'),
                count($files),
                bytes_to_human_readable($totalSize)
            ) ?>
        </p>
    </div>
</div>
