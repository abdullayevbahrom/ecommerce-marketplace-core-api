<?php
use yii\helpers\Html;
use yii\bootstrap4\ActiveForm;
use app\widgets\admin_language_tab\AdminLanguageTab;

$this->title = 'Add Product Type';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1><?=$this->title;?></h1>

        <ol class="breadcrumb">
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/'])?>"><i class="fa fa-dashboard"></i> Home</a></li>
            <li><a href="<?=Yii::$app->urlManager->createUrl(['/admin/product-type/'])?>">Product Types</a></li>
            <li class="active"><?=$this->title;?></li>
        </ol>
    </section>
    <section class="content">
        <?php if (Yii::$app->session->hasFlash('product_type_error')) {?>
            <div class="callout callout-danger text-center">
                <i class="fa fa-exclamation-triangle"></i> <?=Yii::$app->session->getFlash('product_type_error');?>
            </div>
        <?php }?>
        
        <?=AdminLanguageTab::widget();?>
        <br/>
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]);?>
            
            <div class="box box-info color-palette-box">
                <div class="box-header">
                    General Information
                </div>
                <div class="box-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'category_id')->dropDownList(
                                $categories,
                                [
                                    'class'=>'form-control select2',
                                    'prompt'=>'Select category'
                                ]
                            )->label('Category:');?>
                        </div>
                        <div class="col-sm-6">
                            <?=$form->field($model, 'type')->dropDownList([
                                'input' => 'Input (Text field)',
                                'select' => 'Select (Dropdown with predefined values)',
                                'checkbox' => 'Checkbox (Multiple choice)',
                                'range' => 'Range (Min-Max values)'
                            ], [
                                'class'=>'form-control select2',
                                'prompt'=>'Select type'
                            ])->label('Input Type:');?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <?=$form->field($model, 'sort')->textInput()->input('number')->label('Sort Order:');?>
                        </div>
                        <div class="col-sm-6">
                            <label>&nbsp;</label>
                            <div class="form-group">
                                <button type="button" class="btn btn-info btn-sm" id="show-values-btn" onclick="showValuesSection()" style="margin-top: 5px;">
                                    <i class="fa fa-plus-circle"></i> Add More Values
                                </button>
                                <small class="help-block">Click to add predefined values for this type</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="box box-info color-palette-box">
                <div class="box-header">
                    Names
                </div>
                <div class="box-body">
                    <div class="lang-block lang-block-ru">
                        <?=$form->field($model, 'name_ru')->textInput()->label('Name (RU): <span class="error_field">*</span>');?>
                    </div>
                    <div class="lang-block lang-block-en">
                        <?=$form->field($model, 'name_en')->textInput()->label('Name (EN):');?>
                    </div>
                    <div class="lang-block lang-block-uz">
                        <?=$form->field($model, 'name_uz')->textInput()->label('Name (UZ):');?>
                    </div>
                </div>
            </div>

            <div class="box box-info color-palette-box">
                <div class="box-header">
                    Descriptions
                </div>
                <div class="box-body">
                    <div class="lang-block lang-block-ru">
                        <?=$form->field($model, 'description_ru')->textarea(['rows' => 3])->label('Description (RU):');?>
                    </div>
                    <div class="lang-block lang-block-en">
                        <?=$form->field($model, 'description_en')->textarea(['rows' => 3])->label('Description (EN):');?>
                    </div>
                    <div class="lang-block lang-block-uz">
                        <?=$form->field($model, 'description_uz')->textarea(['rows' => 3])->label('Description (UZ):');?>
                    </div>
                </div>
            </div>

            <div class="box box-info color-palette-box" id="values-section" style="display: none;">
                <div class="box-header">
                    <h3 class="box-title">Predefined Values</h3>
                    <div class="pull-right">
                        <button type="button" class="btn btn-secondary btn-sm" id="hide-values-btn" onclick="hideValuesSection()" style="margin-right: 5px;">
                            <i class="fa fa-eye-slash"></i> Hide Section
                        </button>
                        <button type="button" class="btn btn-success btn-sm" id="add-value" onclick="addNewValue()">
                            <i class="fa fa-plus"></i> Add Value
                        </button>
                    </div>
                </div>
                <div class="box-body">
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> These values will be available for selection when this type is used in products. You can add multiple values and set their display order.
                    </div>
                    
                    <div class="row" style="margin-bottom: 10px; font-weight: bold; border-bottom: 1px solid #ddd; padding-bottom: 5px;">
                        <div class="col-sm-3">Value (Russian) *</div>
                        <div class="col-sm-3">Value (English)</div>
                        <div class="col-sm-3">Value (Uzbek)</div>
                        <div class="col-sm-2">Sort Order</div>
                        <div class="col-sm-1">Actions</div>
                    </div>
                    
                    <div id="values-container">
                        <!-- Values will be added here via JavaScript -->
                    </div>
                    
                    <div class="text-center" id="no-values-message" style="padding: 20px; color: #999;">
                        <i class="fa fa-info-circle"></i> No predefined values added yet. Click "Add Value" to start.
                    </div>
                </div>
            </div>

            <div class="box box-info color-palette-box">
                <div class="box-footer">
                    <div class="text-right">
                        <a href="<?=Yii::$app->urlManager->createUrl(['/admin/product-type'])?>" class="btn btn-default">Cancel</a>
                        <?=Html::submitButton('<i class="fa fa-check-square-o"></i> Save', ['class'=>'btn btn-primary', 'id'=>'save-btn'])?>
                    </div>
                </div>
            </div>

        <?php ActiveForm::end();?>
    </section>
</div>

<script>
// Simple function to show values section (onclick fallback)
function showValuesSection() {
    console.log('showValuesSection called'); // Debug
    
    // Force show the section
    $('#values-section').css('display', 'block');
    $('#values-section').show();
    
    // Update button
    $('#show-values-btn').text('Values Section Shown').removeClass('btn-info').addClass('btn-success');
    
    // Update message visibility
    var valueCount = $('#values-container .value-item').length;
    console.log('Value count:', valueCount); // Debug
    
    if (valueCount > 0) {
        $('#no-values-message').hide();
    } else {
        $('#no-values-message').show();
        // Auto-add first value
        setTimeout(function() {
            addNewValue();
        }, 100);
    }
    
    console.log('Values section visible after show:', $('#values-section').is(':visible')); // Debug
}

// Simple function to hide values section
function hideValuesSection() {
    console.log('hideValuesSection called'); // Debug
    
    // Hide the section
    $('#values-section').hide();
    
    // Reset show button
    $('#show-values-btn').text('Add More Values').removeClass('btn-success').addClass('btn-info');
    
    console.log('Values section hidden'); // Debug
}

// Simple function to add new value
function addNewValue() {
    console.log('addNewValue called'); // Debug
    
    var index = $('#values-container .value-item').length;
    console.log('Adding value at index:', index); // Debug
    
    var html = '<div class="value-item" style="margin-bottom: 15px; padding: 10px; border: 1px solid #e3e3e3; border-radius: 4px; background-color: #fafafa;">' +
               '<div class="row">' +
               '<div class="col-sm-3">' +
               '<input type="text" name="values[' + index + '][value_ru]" class="form-control" placeholder="Value (Russian)" required>' +
               '</div>' +
               '<div class="col-sm-3">' +
               '<input type="text" name="values[' + index + '][value_en]" class="form-control" placeholder="Value (English)">' +
               '</div>' +
               '<div class="col-sm-3">' +
               '<input type="text" name="values[' + index + '][value_uz]" class="form-control" placeholder="Value (Uzbek)">' +
               '</div>' +
               '<div class="col-sm-2">' +
               '<input type="number" name="values[' + index + '][sort]" class="form-control" placeholder="Sort" value="' + ((index + 1) * 10) + '" min="0">' +
               '</div>' +
               '<div class="col-sm-1">' +
               '<button type="button" class="btn btn-danger btn-sm remove-value" onclick="removeValue(this)" title="Remove this value">' +
               '<i class="fa fa-trash"></i>' +
               '</button>' +
               '</div>' +
               '</div>' +
               '</div>';
    
    $('#values-container').append(html);
    
    // Update message visibility
    var valueCount = $('#values-container .value-item').length;
    if (valueCount > 0) {
        $('#no-values-message').hide();
    } else {
        $('#no-values-message').show();
    }
    
    // Focus on the first input of the new value
    $('#values-container .value-item').last().find('input[name*="value_ru"]').focus();
    
    console.log('Value added successfully'); // Debug
}

// Simple function to remove value
function removeValue(button) {
    console.log('removeValue called'); // Debug
    
    if (confirm('Are you sure you want to remove this value?')) {
        $(button).closest('.value-item').remove();
        
        // Update message visibility
        var valueCount = $('#values-container .value-item').length;
        if (valueCount > 0) {
            $('#no-values-message').hide();
        } else {
            $('#no-values-message').show();
        }
        
        // Reindex the remaining items
        $('#values-container .value-item').each(function(index) {
            $(this).find('input[name*="[value_ru]"]').attr('name', 'values[' + index + '][value_ru]');
            $(this).find('input[name*="[value_en]"]').attr('name', 'values[' + index + '][value_en]');
            $(this).find('input[name*="[value_uz]"]').attr('name', 'values[' + index + '][value_uz]');
            $(this).find('input[name*="[sort]"]').attr('name', 'values[' + index + '][sort]');
        });
        
        console.log('Value removed and reindexed'); // Debug
    }
}

$(document).ready(function() {
    // Function to update the "no values" message visibility
    function updateNoValuesMessage() {
        var valueCount = $('#values-container .value-item').length;
        if (valueCount > 0) {
            $('#no-values-message').hide();
        } else {
            $('#no-values-message').show();
        }
    }

    // Function to handle type change
    function handleTypeChange() {
        var selectedType = $('#producttype-type').val();
        
        if (selectedType === 'select' || selectedType === 'checkbox') {
            $('#values-section').show();
            updateNoValuesMessage();
            $('#show-values-btn').text('Values Section Shown').removeClass('btn-info').addClass('btn-success');
            
            // Add first value if none exist
            if ($('#values-container .value-item').length === 0) {
                addNewValue();
            }
        } else {
            $('#values-section').hide();
            $('#values-container').empty();
            updateNoValuesMessage();
            $('#show-values-btn').text('Add More Values').removeClass('btn-success').addClass('btn-info');
        }
    }

    // Bind change events - works with both regular select and Select2
    $('#producttype-type').on('change select2:select select2:unselect', function() {
        handleTypeChange();
    });

    // Also use event delegation as a fallback for Select2
    $(document).on('change', '#producttype-type', function() {
        handleTypeChange();
    });

    // Fallback jQuery event handlers (onclick is primary)
    $(document).on('click', '#show-values-btn', showValuesSection);
    $(document).on('click', '#hide-values-btn', hideValuesSection);
    $(document).on('click', '#add-value', addNewValue);

    // Fallback jQuery event handlers for dynamically created remove buttons
    $(document).on('click', '.remove-value', function(e) {
        e.preventDefault();
        removeValue(this);
    });

    // Initialize on page load - check initial value after a small delay for Select2
    setTimeout(function() {
        handleTypeChange();
    }, 100);

    // Form validation before submission
    $('form').on('submit', function(e) {
        var selectedType = $('#producttype-type').val();
        
        if (selectedType === 'select' || selectedType === 'checkbox') {
            var hasValues = false;
            var hasValidValue = false;
            
            $('#values-container .value-item').each(function() {
                hasValues = true;
                var ruValue = $(this).find('input[name*="[value_ru]"]').val().trim();
                if (ruValue !== '') {
                    hasValidValue = true;
                    return false; // break the loop
                }
            });
            
            if (!hasValues || !hasValidValue) {
                e.preventDefault();
                alert('Please add at least one predefined value for ' + selectedType + ' type.\n\nThe Russian value is required for each predefined option.');
                
                // Show the values section if it's hidden
                $('#values-section').show();
                
                // Add a value if none exist
                if (!hasValues) {
                    $('#add-value').click();
                }
                
                return false;
            }
            
            // Check that all visible values have Russian text
            var emptyValues = [];
            $('#values-container .value-item').each(function(index) {
                var ruValue = $(this).find('input[name*="[value_ru]"]').val().trim();
                if (ruValue === '') {
                    emptyValues.push(index + 1);
                }
            });
            
            if (emptyValues.length > 0) {
                e.preventDefault();
                alert('Please fill in the Russian value for all predefined values.\n\nEmpty values found at positions: ' + emptyValues.join(', '));
                return false;
            }
        }
    });
});
</script>

<style>
.value-item {
    transition: all 0.3s ease;
}

.value-item:hover {
    background-color: #f0f8ff !important;
    border-color: #007bff !important;
}

.value-item input[required] {
    border-left: 3px solid #007bff;
}

.value-item input:focus {
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
    border-color: #007bff;
}

.remove-value:hover {
    background-color: #dc3545 !important;
    transform: scale(1.1);
}

#add-value:hover {
    background-color: #218838 !important;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

#values-section .box-header {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

#no-values-message {
    background-color: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    margin: 10px 0;
}

.alert-info {
    border-left: 4px solid #17a2b8;
}

/* Add More Values button styling */
#show-values-btn {
    transition: all 0.3s ease;
    min-width: 140px;
}

#show-values-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

#show-values-btn.btn-info:hover {
    background-color: #138496;
}

#show-values-btn.btn-success:hover {
    background-color: #1e7e34;
}

#show-values-btn .fa {
    margin-right: 5px;
}

/* Hide Values button styling */
#hide-values-btn {
    transition: all 0.3s ease;
}

#hide-values-btn:hover {
    background-color: #6c757d;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

#hide-values-btn .fa {
    margin-right: 5px;
}
</style>