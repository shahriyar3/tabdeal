<?php
$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('mauticContent', 'customform');
$view['slots']->set('headerTitle', $view['translator']->trans('mautic.customform.title'));
?>

<div class="box-layout">
    <div class="col-md-9 bg-auto height-auto">
        <div class="pa-md">
            <div class="form-group">
                <div class="row">
                    <div class="col-md-12">
                        <?php echo $view['form']->form($form); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 bg-white height-auto">
        <div class="pr-lg pl-lg pt-md pb-md">
            <h4><?php echo $view['translator']->trans('mautic.customform.description'); ?></h4>
            <p class="text-muted">
                This plugin provides a custom form with a checkbox and two text fields that can be configured through the admin panel.
            </p>
        </div>
    </div>
</div> 