<?php $this->Breadcrumbs->add(__('Smilies'), '/admin/smilies'); ?>
<?php $this->Breadcrumbs->add(__('Smiley Codes'), '/admin/smiley_codes'); ?>
<?php $this->Breadcrumbs->add(__('Edit Smiley Code'), false); ?>
<h1><?php echo __('Edit Smiley Code'); ?></h1>
<div class="smileyCodes form">
    <?php echo $this->Form->create($smiley); ?>
    <fieldset>
        <?php
        echo $this->Form->control('smiley_id');
        echo $this->Form->control('code');
        ?>
    </fieldset>
    <?php
    echo $this->Form->submit(
        __('Submit'),
        [
            'class' => 'btn btn-primary',
        ]
    );
    echo $this->Form->end();
    ?>
</div>
