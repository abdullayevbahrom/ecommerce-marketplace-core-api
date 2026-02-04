<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;

use app\models\user\User;

$this->title = 'Save user';
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>
        
        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php $form = ActiveForm::begin(); ?>
            <div class="box box-info color-palette-box">
                <div class="box-header">Main info</div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-5">
                            <div class="row">
                                <div class="col-xs-6"> 
                                    <?=$form->field($model, 'imageFiles[]')->fileInput(['class'=>'file-upload-ajax'])->label('Photo'); ?>
                                </div>
                                <div class="col-xs-6">
                                    <img src="<?=$model->getPhoto('200x200/')?>" width="200" class="photo-admin-user"/>
                                    <?php if ($model->image) {?>
                                        <br/><br/>
                                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/default/remove-photo', 'id'=>$model->image->id])?>" class="edit-user remove-object btn btn-sm btn-danger"><i class="fa fa-remove"></i> Delete photo</a>
                                    <?php }?>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-7">
                            <div class="row">
                                <div class="col-sm-4">
                                    <?=$form->field($model, 'name')->textInput()->input('text')->label('First Name');?>
                                </div>
                                <div class="col-sm-4">
                                    <?=$form->field($model, 'lastname')->textInput()->input('text')->label('Last Name');?>
                                </div>
                                <div class="col-sm-4">
                                    <?=$form->field($model, 'middlename')->textInput()->input('text')->label('Middle Name');?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <?=$form->field($model, 'phone')->textInput()->input('text', ['class'=>'form-control '])->label('Phone');?>
                                </div>
                                <div class="col-sm-6">
                                    <?=$form->field($model, 'organization_name')->textInput()->input('text')->label('Organization Name');?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <?=$form->field($model, 'email')->textInput()->input('text')->label('E-mail');?>
                                </div>
                                <div class="col-sm-6">
                                    <?=$form->field($model, 'password')->textInput()->input('password', ['value'=>''])->label('Password');?>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-6">
                                    <?=$form->field($model, 'birthday')->textInput()->input('text', ['class'=>'form-control datepicker'])->label('Date birth');?>
                                </div>
                                <div class="col-sm-6">
                                    <?= $form->field($model, 'gender')->radioList(['1' => 'Male', '2' => 'Female'])->label('Gender'); ?>
                                </div>
                            </div>
                            <?=$form->field($model, 'type')->dropDownList([
                                'fiz' => 'Individual',
                                'yur' => 'Entity'
                            ])->label('Type');?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="box box-info color-palette-box" id="yur-data" style="display:<?=($model->type == 'yur') ? 'block' : 'none';?>">
                <div class="box-header">Legal details</div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'inn')->textInput()->input('text')->label('TIN');?>
                        </div>
                        <div class="col-sm-6">
                            <?=$form->field($model, 'account')->textInput()->input('text')->label('Checking account');?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'bank')->textInput()->input('text')->label('Bank');?>
                        </div>
                        <div class="col-sm-6">
                            <?=$form->field($model, 'oked')->textInput()->input('text')->label('OKED');?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'okohx')->textInput()->input('text')->label('OKOHX');?>
                        </div>
                        <div class="col-sm-6">
                            <?=$form->field($model, 'mfo')->textInput()->input('text')->label('МФО');?>
                        </div>
                    </div>
                    <?=$form->field($model, 'address_legal')->textInput()->input('text')->label('Legal addres');?>
                </div>
            </div>
            <div class="box box-info color-palette-box">
                <div class="box-header">Address</div>
                <div class="box-body">
                    <div id="item-form">
                        <?php if ($model->addresses) {?>
                            <?php foreach ($model->addresses as $v) {?>
                                <div class="item-block">
                                    <div class="row">
                                        <div class="col-sm-10">
                                            <?=$form->field($model, 'address[]')->textInput()->input('text', ['value'=>$v->address, 'placeholder'=>'Enter address'])->label(false);?>
                                        </div>
                                        <div class="col-sm-2">
                                            <a href="javascript:;" class="btn btn-danger remove-block" title="Delete"><i class="fa fa-remove"></i> </a>
                                        </div>
                                    </div>
                                </div>
                            <?php }?>
                        <?php } else {?>
                            <div class="item-block">
                                <div class="row">
                                    <div class="col-sm-10">
                                        <?=$form->field($model, 'address[]')->textInput()->input('text', ['placeholder'=>'Enter address'])->label(false);?>
                                    </div>
                                    <div class="col-sm-2">
                                        <a href="javascript:;" class="btn btn-danger remove-block"><i class="fa fa-remove"></i> </a>
                                    </div>
                                </div>
                            </div>
                        <?php }?>
                    </div>

                    <div class="text-center">
                        <a href="javascript:;" class="add-variant-item btn btn-success btn-sm">
                            <i class="glyphicon glyphicon-plus"></i> Add more
                        </a>
                    </div>
                </div>
                <div class="box-footer">
                    <div class="text-right">
                        <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Save', ['class'=>'btn btn-primary']);?>
                    </div>
                </div>
            </div>
            
            <!-- BTS Region and City -->
            <div class="box box-info color-palette-box">
                <div class="box-header">BTS Location</div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <?= $form->field($model, 'bts_region_id')->dropDownList(
                                \yii\services\BTS::getRegions('ru'),
                                [
                                    'class' => 'form-control select2',
                                    'prompt' => 'Select Region',
                                    'id' => 'user-region'
                                ]
                            )->label('BTS Region'); ?>
                        </div>
                        <div class="col-sm-6">
                            <?php
                            // Get current cities if model has region selected
                            $currentCities = [];
                            if ($model->bts_region_id) {
                                $cities = \yii\services\BTS::getCities($model->bts_region_id, 'ru');
                                foreach ($cities as $id => $city) {
                                    $currentCities[$id] = $city['name'];
                                }
                            }
                            ?>
                            <?= $form->field($model, 'bts_city_id')->dropDownList(
                                $currentCities,
                                [
                                    'class' => 'form-control select2',
                                    'prompt' => 'Select City',
                                    'id' => 'user-city',
                                    
                                    'disabled' => empty($model->bts_region_id)
                                ]
                            )->label('BTS City'); ?>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="icon fa fa-info"></i>
                        <strong>Note:</strong> Select a region first, then choose the corresponding city. Cities are filtered based on the selected region.
                    </div>
                </div>
                <div class="box-footer">
                    <div class="text-right">
                        <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Save', ['class'=>'btn btn-primary']);?>
                    </div>
                </div>
            </div>
        <?php ActiveForm::end()?>
    </section>
</div>

<?php
$script = <<<JS
    $('#user-type').on('change', function() {
        if ($(this).val() == 'fiz') {
            $('#yur-data').hide();
        }
        if ($(this).val() == 'yur') {
            $('#yur-data').show();
        }
    });
    
    // BTS Region/City dynamic loading
    $('#user-region').on('change', function() {
        var regionId = $(this).val();
        var citySelect = $('#user-city');
        
        if (regionId) {
            // Show loading state
            citySelect.prop('disabled', true).html('<option value="">Loading cities...</option>');
            
            // Load cities for selected region
            $.ajax({
                url: '/admin/user/get-cities',
                type: 'GET',
                data: { region_id: regionId },
                dataType: 'json',
                success: function(data) {
                    citySelect.html('<option value="">Select City</option>');
                    if (data.success && data.cities) {
                        $.each(data.cities, function(id, name) {
                            citySelect.append('<option value="' + id + '">' + name + '</option>');
                        });
                    }
                    citySelect.prop('disabled', false);
                },
                error: function() {
                    citySelect.html('<option value="">Error loading cities</option>');
                    citySelect.prop('disabled', false);
                    alert('Error loading cities. Please try again.');
                }
            });
        } else {
            citySelect.html('<option value="">Select City</option>').prop('disabled', true);
        }
    });
    
    // Initialize city dropdown on page load if region is already selected
    var currentRegionId = $('#user-region').val();
    var currentCityId = $('#user-city').find('option[selected]').val();
    if (currentRegionId && currentCityId) {
        $('#user-region').trigger('change');
        setTimeout(function() {
            $('#user-city').val(currentCityId);
        }, 500);
    }
JS;

$this->registerJs($script);
?>