<table class="table table-condensed">
    <thead>
        <tr>
            <th><?= _('Name') ?></th>
            <th><?= _('Size') ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if(strlen($path->relative)): ?>
        <tr>
            <td><a href="./../">..</a></td>
            <td></td>
        </tr>
        <?php endif; ?>
        <?php foreach ($files as $file): ?>
        <tr>
            <td><?= file_to_link($file, $path) ?></td>
            <td><?= e($file->getSize()) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
