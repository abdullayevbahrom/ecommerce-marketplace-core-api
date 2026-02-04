$(function() {
    // item list
	$('.dd').nestable({
        collapsedClass:'dd-collapsed',
     }).nestable('collapseAll');

     // categories
    // view
    $(document).on('click', '.view_category', function() {
        var id = $(this).attr('data-value');
        $('#category-popular-view').html('');

        $.ajax({
            url: '/admin/category/get-category',
            type: 'post',
            data: {'id':id},
            beforeSend: function() {
                $('#category-view-block').hide();
                $('#preloader').show();
            },
            success: function(data) {
                if (data['category']['popular'] == 1) {
                    $('#category-popular-view').html('<i class="fa fa-check-circle"></i> Популярная категория')
                }
                $('#category-view-icon').attr('src', data['category']['photo']);
                $('#category-name-ru').html(data['category']['name_ru']);
                $('#category-description-ru').html(data['category']['description_ru']);
                $('#category-option-ru').html(data['category']['option_ru']);
                $('#category-name-uz').html(data['category']['name_uz']);
                $('#category-description-uz').html(data['category']['description_uz']);
                $('#category-option-uz').html(data['category']['option_uz']);
                $('#category-name-en').html(data['category']['name_en']);
                $('#category-description-en').html(data['category']['description_en']);
                $('#category-option-en').html(data['category']['option_en']);
                $('#preloader').hide();
                $('#category-view-block').fadeIn();
            }
        });

        return false;
    });

    // add / update subcategory
    $(document).on('click', '.update_category', function() {
        var id = $(this).attr('data-value');

        $('#category-update-popular').removeAttr('checked');

        $('.category-filter').hide();
        $('.value-'+id).show();

        $.ajax({
            url: '/admin/category/get-category',
            type: 'post',
            data: {'id':id},
            beforeSend: function() {
                $('#category-update-block').hide();
                $('#preloader-update').show();
            },
            success: function(data) {
                if (data['category']['popular'] == 1) {
                    $('#category-update-popular').attr('checked', 'checked');
                }

                if (data['category']['photo_id']) {
                    $('#remove-photo').attr('href', '/admin/default/remove-photo?id='+data['category']['photo_id']);
                    $('#remove-photo').show();
                } else {
                    $('#remove-photo').hide();
                }
                $('#category-update-id').val(data['category']['id']);
                $('#category-update-icon').attr('src', data['category']['photo']);
                $('#category-update-name-ru').val(data['category']['name_ru']);
                CKEDITOR.instances['category-update-description-ru'].setData(data['category']['description_ru']);
                // CKEDITOR.instances['category-update-option-ru'].setData(data['category']['option_ru']);
                $('#category-update-name-uz').val(data['category']['name_uz']);
                CKEDITOR.instances['category-update-description-uz'].setData(data['category']['description_uz']);
                // CKEDITOR.instances['category-update-option-uz'].setData(data['category']['option_uz']);
                $('#category-update-name-en').val(data['category']['name_en']);
                CKEDITOR.instances['category-update-description-en'].setData(data['category']['description_en']);
                // CKEDITOR.instances['category-update-option-uz'].setData(data['category']['option_uz']);
                $('#preloader-update').hide();
                $('#category-update-block').fadeIn();

                if (data['category']['main'] == 1) {
                    $('#check-main').attr('checked', 'checked');
                } else {
                    $('#check-main').removeAttr('checked');
                }
            }
        });

        return false;
    });

    // add subcategory
    $(document).on('click', '.add_category', function(){
        var id = $(this).attr('data-value');

        $.ajax({
            url: '/admin/category/get-category',
            type: 'post',
            data: {'id':id},
            beforeSend: function() {
                $('#category-add-block').hide();
                $('#preloader-add').show();
            },
            success: function(data) {
                
                if (data) {
                    $('#category-add-parent_id').val(data['category']['id']);
                    $('#preloader-add').hide();
                    $('#category-add-block').fadeIn();
                }
            }
        });

        return false;
    });

    // languages
    $(document).on('click', '.lang-button', function() {
        $('.lang-button').removeClass('btn-default').addClass('btn-primary');
        $(this).removeClass('btn-primary').addClass('btn-default');

        var val = $(this).find('img').attr('class').split('-');
        val = val[1];

        $('.lang-block').hide();
        $('.lang-block-'+val).show();

        return false;
    });

    $("#save-category-sort").on('click', function(){
        var count = 0, top = [];
        $('.top').each(function(i) {
            count++;
            top[i] = count+'-'+$(this).find('input').val();
        });

        top = top.join(',');
        //alert(top);

        var count_second = 0, second = [];
        $('.second').each(function(i){
            count_second++;
            second[i] = count_second+'-'+$('.input-'+$(this).attr('data-id')).val();
        });

        second = second.join(',');
        //alert(second);

        var count_third = 0, third = [];
        $('.third').each(function(i){
            count_third++;
            third[i] = count_third+'-'+$('.input-'+$(this).attr('data-id')).val();
        });

        third = third.join(',');
        //alert(third);

        $.ajax({
            url: '/admin/category/save-sort',
            type: 'post',
            data: {'top':top, 'second':second, 'third':third},
            success: function(data) {
                $('.category-result').hide();
                if (data['save'] === true) {
                    $('#save-category-success').show();
                } else {
                    $('#save-category-error').show();
                }
            }
        });
        return false;
    });

    // remove dialog window
    $('.remove-object').on('click', function(event){
        event.stopPropagation();
        if(!confirm('Вы уверены, что хотите удалить?')){
            return false;
        }
    });

    // validation mask
    if ($("input").is(".sms-phone")) {
        $(".sms-phone").inputmask("+998*********");
    }
    if ($("input").is(".card-number")) {
        $(".card-number").inputmask("****************");
    }
    if ($("input").is(".card-expire")) {
        $(".card-expire").inputmask("****");
    }

    // multiply checbox
    $(document).on('click', '#action-links a', function() {
        var ids = [];
        var count = 0;

        var action = $(this).attr('data-value');
        var url = window.location.href.split('/');
        var page = url[4];

        $("#item-block input[type=checkbox]").each(function() {
            if ($(this).prop('checked')) {
                ids[count++] = $(this).val();
            }
        });

        if (ids.length > 0) {
            $.ajax({
                url: '/admin/default/change-all',
                type: 'post',
                data: {'ids':ids, 'action':action, 'page':page},
                success: function(data) {
                    // console.log(data);
                }
            });
        }

        return false;
    });

    $(document).on('change', '#item-block input[type=checkbox]', function() {
        var check = false;

        $("#item-block input[type=checkbox]").each(function() {
            if ($(this).prop('checked')) {
                check = true;
            }
        });

        if (check === true) {
            $('#action-links').show();
        } else {
            $('#action-links').hide();
        }
    });

    // location href table
    $('#item-block td').click(function (e) {
        var url = $(this).closest('tr').attr('url');
        if (e.target == this) {
            location.href = url;
        }
    });

    // dynamic blocks - add filter option
    $(document).on('click', '.add-item', function() {
        var block = $('#item-form .item-block').last().clone();

        // Clear all input values in the cloned block
        block.find('input').val('');
        
        // Update field names to maintain proper array indexing
        var itemCount = $('#item-form .item-block').length;
        block.find('input').each(function() {
            var name = $(this).attr('name');
            if (name) {
                // Update array index in field names
                var newName = name.replace(/\[\d+\]/, '[' + itemCount + ']');
                $(this).attr('name', newName);
                $(this).attr('id', $(this).attr('id').replace(/-\d+-/, '-' + itemCount + '-'));
            }
        });
        
        // Update labels' for attributes
        block.find('label').each(function() {
            var forAttr = $(this).attr('for');
            if (forAttr) {
                var newFor = forAttr.replace(/-\d+-/, '-' + itemCount + '-');
                $(this).attr('for', newFor);
            }
        });

        $('#item-form').append(block);
    });
    
    // Remove filter option
    $(document).on('click', '.remove-item', function() {
        if ($('#item-form .item-block').length > 1) {
            $(this).closest('.item-block').remove();
        } else {
            alert('Для фильтров типа выбор/чекбокс требуется минимум один вариант.');
        }
    });

    $('.select2').select2();
    $('.date-range').daterangepicker();
    $('.datepicker').datepicker({
        autoclose: true
    });

    // category without filters
    $(document).on('change', '.category-item-simple-region', function() {
        var block = $(this).closest('#category-block');
        var id = $(this).val();
        var page = $('#current-page').val();

        $(this).closest('.form-group').nextAll('.category-group').remove();

        if ($('#logistregion-region_id').val() == '') {
            $('#main-form').hide();
        } else {
            $('#main-form').show();
        }

        if (id) {
            $.ajax({
                url: '/admin/category/get-categories',
                type: 'post',
                data: {'id':id},
                success: function(data) {
                    // subcategories
                    if (data['categories'] && (data['categories'].length > 0)) {
                        var select = '<div class="category-group form-group"><label for="'+page+'-category_id">Подкатегория</label><select name="'+page+'[sub_region_id][]" class="category-item-simple-region form-control select2"><option value="">Выбрать подкатегорию</option>';
                    
                        for (var i in data['categories']) {
                            select += '<option value="'+data['categories'][i]['id']+'">'+data['categories'][i]['name']+'</option>';
                        }

                        select += '</select></div>';

                        block.append(select);
                        $('.select2').select2();
                    }
                }
            });
        }
    });

    $(document).on('change', '.category-item-simple-region-a', function() {
        var block = $(this).closest('#category-block_a');
        var id = $(this).val();
        var page = $('#current-page').val();

        $(this).closest('.form-group').nextAll('.category-group').remove();

        if (id) {
            $.ajax({
                url: '/admin/category/get-categories',
                type: 'post',
                data: {'id':id},
                success: function(data) {
                    // subcategories
                    if (data['categories'] && (data['categories'].length > 0)) {
                        var select = '<div class="category-group form-group"><label for="'+page+'-category_id">Подкатегория</label><select name="'+page+'[sub_region_a_id][]" class="category-item-simple-region-a form-control select2"><option value="">Выбрать подкатегорию</option>';
                    
                        for (var i in data['categories']) {
                            select += '<option value="'+data['categories'][i]['id']+'">'+data['categories'][i]['name']+'</option>';
                        }

                        select += '</select></div>';

                        block.append(select);
                        $('.select2').select2();
                    }
                }
            });
        }
    });

    // category without filters
    $(document).on('change', '.category-item-simple', function() {
        var block = $(this).closest('#category-block');
        var id = $(this).val();
        var page = $('#current-page').val();

        $(this).closest('.form-group').nextAll('.category-group').remove();

        if (id) {
            $.ajax({
                url: '/admin/category/get-categories',
                type: 'post',
                data: {'id':id},
                success: function(data) {
                    // subcategories
                    if (data['categories'] && (data['categories'].length > 0)) {
                        var select = '<div class="category-group form-group"><label for="'+page+'-category_id">Подкатегория</label><select name="'+page+'[sub_category_id][]" class="category-item-simple form-control select2"><option value="">Выбрать подкатегорию</option>';
                    
                        for (var i in data['categories']) {
                            select += '<option value="'+data['categories'][i]['id']+'">'+data['categories'][i]['name']+'</option>';
                        }

                        select += '</select></div>';

                        block.append(select);
                        $('.select2').select2();
                    }
                }
            });
        }
    });

    // category
    $(document).on('change', '.category-item', function() {
        var block = $(this).closest('#category-block');
        var id = $(this).val();
        
        // Determine the correct URL based on current path
        var currentPath = window.location.pathname;
        var categoryUrl = '/admin/category/get-categories';
        if (currentPath.includes('/shop/')) {
            categoryUrl = '/shop/category/get-categories';
        }

        $(this).closest('.form-group').nextAll('.category-group').remove();

        if (id) {
            $.ajax({
                url: categoryUrl,
                type: 'post',
                data: {'id':id},
                success: function(data) {
                    console.log(data);
                    // subcategories
                    if (data['categories'] && (data['categories'].length > 0)) {
                        var select = '<div class="category-group form-group"><label for="product-category_id">Подкатегория</label><select name="Product[sub_category_id][]" class="category-item form-control select2"><option value="">Выбрать подкатегорию</option>';
                    
                        for (var i in data['categories']) {
                            var categoryName = data['categories'][i]['name_ru'] || data['categories'][i]['name_en'] || data['categories'][i]['name_uz'] || data['categories'][i]['name'] || 'Category';
                            select += '<option value="'+data['categories'][i]['id']+'">'+categoryName+'</option>';
                        }

                        select += '</select></div>';

                        block.append(select);
                        $('.select2').select2();
                    }

                    // product types
                    console.log('Processing product types:', data['product_types']);
                    if (data['product_types'] && (data['product_types'].length > 0)) {
                        console.log('Found product types, count:', data['product_types'].length);
                        var html = '<div class="box box-info color-palette-box" id="product-types-box">';
                        html += '<div class="box-header with-border">';
                        html += '<h3 class="box-title"><i class="fa fa-cubes"></i> Product Types</h3>';
                        html += '</div>';
                        html += '<div class="box-body">';
                        html += '<div class="alert alert-info">';
                        html += '<i class="fa fa-info-circle"></i> Select multiple values to create product variants. Each combination will create a separate product.';
                        html += '</div>';
                        
                        for (var i in data['product_types']) {
                            var productType = data['product_types'][i];
                            console.log('Processing product type:', productType.name, 'with values:', productType.values);
                            
                            html += '<div class="form-group product-type-group">';
                            html += '<label class="control-label" style="font-weight: bold; color: #3c8dbc;">';
                            html += '<i class="fa fa-tag"></i> ' + (productType.name || productType.name_ru || 'Product Type');
                            html += '</label>';
                            
                            if (productType.description) {
                                html += '<p class="help-block" style="margin-top: 5px; font-style: italic; color: #666;">' + productType.description + '</p>';
                            }
                            
                            html += '<div class="product-type-values" style="margin-top: 10px; padding: 10px; background-color: #f9f9f9; border-radius: 4px;">';
                            
                            if (productType.type == 'select' && productType.values && productType.values.length > 0) {
                                html += '<div class="row">';
                                for (var v in productType.values) {
                                    var value = productType.values[v];
                                    html += '<div class="col-md-2 col-sm-3 col-xs-4" style="margin-bottom: 8px;">';
                                    html += '<label class="checkbox-inline" style="margin: 0; padding: 5px 8px; border: 1px solid #ddd; border-radius: 3px; background-color: white; cursor: pointer; display: block; text-align: center;">';
                                    html += '<input type="checkbox" name="Product[product_types][' + productType.id + '][]" value="' + value.id + '" style="margin-right: 5px;"/> ';
                                    html += '<span style="font-weight: normal;">' + (value.display_value || value.value) + '</span>';
                                    html += '</label>';
                                    html += '</div>';
                                }
                                html += '</div>';
                            } else if (productType.type == 'checkbox' && productType.values && productType.values.length > 0) {
                                html += '<div class="row">';
                                for (var v in productType.values) {
                                    var value = productType.values[v];
                                    html += '<div class="col-md-3 col-sm-4 col-xs-6" style="margin-bottom: 8px;">';
                                    html += '<label class="checkbox-inline" style="margin: 0; padding: 5px 8px; border: 1px solid #ddd; border-radius: 3px; background-color: white; cursor: pointer; display: block;">';
                                    html += '<input type="checkbox" name="Product[product_types][' + productType.id + '][]" value="' + value.id + '" style="margin-right: 5px;"/> ';
                                    html += '<span style="font-weight: normal;">' + (value.display_value || value.value) + '</span>';
                                    html += '</label>';
                                    html += '</div>';
                                }
                                html += '</div>';
                            } else if (productType.type == 'input') {
                                html += '<input type="text" name="Product[product_types][' + productType.id + ']" class="form-control" placeholder="Enter custom value for ' + (productType.name || productType.name_ru) + '">';
                            } else if (productType.type == 'range') {
                                html += '<div class="row">';
                                html += '<div class="col-md-6">';
                                html += '<input type="number" name="Product[product_types][' + productType.id + '][min]" class="form-control" placeholder="Minimum value">';
                                html += '</div>';
                                html += '<div class="col-md-6">';
                                html += '<input type="number" name="Product[product_types][' + productType.id + '][max]" class="form-control" placeholder="Maximum value">';
                                html += '</div>';
                                html += '</div>';
                            }
                            
                            html += '</div>';
                            html += '</div>';
                            
                            // Add separator line except for the last item
                            var productTypesArray = Object.keys(data['product_types']);
                            if (i < productTypesArray.length - 1) {
                                html += '<hr style="margin: 20px 0;">';
                            }
                        }
                        
                        html += '</div>';
                        html += '</div>';
                        
                        $('#product-types-container').html(html);
                        
                        // Add some CSS for hover effects
                        $('<style>')
                            .prop('type', 'text/css')
                            .html(`
                                .product-type-values label:hover {
                                    background-color: #e6f3ff !important;
                                    border-color: #3c8dbc !important;
                                }
                                .product-type-values input[type="checkbox"]:checked + span {
                                    font-weight: bold;
                                    color: #3c8dbc;
                                }
                                .product-type-values label:has(input[type="checkbox"]:checked) {
                                    background-color: #d9edf7 !important;
                                    border-color: #3c8dbc !important;
                                }
                            `)
                            .appendTo('head');
                    }

                    // filters - Show ALL filters regardless of is_filter value
                    console.log('Processing filters:', data['filters']);
                    if (data['filters'] && (data['filters'].length > 0)) {
                        console.log('Total filters found:', data['filters'].length);
                        
                        for (var i in data['filters']) {
                            var filter = data['filters'][i];
                            var filterName = filter.name_ru || filter.name_en || filter.name_uz || filter.name || 'Filter';
                            var filterValue = filter.value;
                            var filterChilds = filter.childs || [];
                            
                            console.log('Rendering filter:', filterName, 'is_filter:', filter.is_filter, 'type:', filter.type, 'childs:', filterChilds.length);
                            
                            if (filter.type == 'select' && filterChilds.length > 0) {
                                var select = '<div class="category-group form-group"><label for="product-filter-'+filter.id+'">'+filterName+'</label><select name="Product[filters]['+filter.id+']" class="form-control select2" id="product-filter-'+filter.id+'"><option value="">Выбрать значение</option>';
                    
                                for (var c in filterChilds) {
                                    var child = filterChilds[c];
                                    var childValue = child.value_ru || child.value_en || child.value_uz || child.value || '';
                                    var selected = (filterValue == childValue) ? 'selected' : '';
                                    select += '<option value="'+childValue+'" '+selected+'>'+childValue+'</option>';
                                }

                                select += '</select></div>';
                                block.append(select);
                                $('.select2').select2();
                            }
                            else if (filter.type == 'input') {
                                var inputValue = filterValue || '';
                                var inputHtml = '<div class="category-group form-group">';
                                inputHtml += '<label for="product-filter-'+filter.id+'">'+filterName+'</label>';
                                inputHtml += '<input type="text" class="form-control" name="Product[filters]['+filter.id+']" id="product-filter-'+filter.id+'" value="'+inputValue+'" placeholder="Введите '+filterName.toLowerCase()+'">';
                                inputHtml += '</div>';
                                block.append(inputHtml);
                            }
                            else if (filter.type == 'checkbox' && filterChilds.length > 0) {
                                var checkbox = '<div class="category-group form-group field-product-filters">';
                                checkbox += '<label>'+filterName+'</label>';
                                checkbox += '<div class="checkbox-group" style="margin-top: 10px;">';

                                for (var c in filterChilds) {
                                    var child = filterChilds[c];
                                    var childValue = child.value_ru || child.value_en || child.value_uz || child.value || '';
                                    var childId = 'filter_'+filter.id+'_'+c;
                                    var checked = '';
                                    
                                    // Check if this value should be checked
                                    if (Array.isArray(filterValue)) {
                                        checked = filterValue.includes(childValue) ? 'checked' : '';
                                    } else if (filterValue == childValue) {
                                        checked = 'checked';
                                    }
                                    
                                    checkbox += '<div class="checkbox" style="margin-bottom: 5px;">';
                                    checkbox += '<label style="font-weight: normal; cursor: pointer;">';
                                    checkbox += '<input type="checkbox" name="Product[filters]['+filter.id+'][]" value="'+childValue+'" id="'+childId+'" '+checked+' style="margin-right: 8px;"> ';
                                    checkbox += childValue;
                                    checkbox += '</label>';
                                    checkbox += '</div>';
                                }

                                checkbox += '</div></div>';
                                block.append(checkbox);
                            }
                        }
                    }
                }
            });
        }
    });

    // filter
    $(document).on('change', '.filter-item', function() {
        var block = $(this).closest('#category-block');
        var id = $(this).val();
        var main_category = $('#filter-category').val();

        $(this).closest('.form-group').nextAll('.filter-group').remove();
        $('#filter-block').hide();

        if (main_category) {
            $('#filter-block').show();
        }

        if (id) {
            $.ajax({
                url: '/admin/category/get-categories',
                type: 'post',
                data: {'id':id},
                success: function(data) {
                    // subcategories
                    if (data['categories'] && (data['categories'].length > 0)) {
                        var select = '<div class="filter-group form-group"><label for="filter-category_id">Подкатегория</label><select name="Filter[sub_category_id][]" class="filter-item form-control select2"><option value="">Выбрать подкатегорию</option>';
                    
                        for (var i in data['categories']) {
                            select += '<option value="'+data['categories'][i]['id']+'">'+data['categories'][i]['name']+'</option>';
                        }

                        select += '</select></div>';

                        block.append(select);
                        $('.select2').select2();
                    }
                }
            });
        }
    });

    // filters
    $('#filter-type').on('change', function() {
        var filterType = $(this).val();
        
        if (filterType == 'input') {
            $('#filter-variable').hide();
            // Show info message for input type
            if ($('#input-type-info').length === 0) {
                $('#filter-block').append('<div id="input-type-info" class="alert alert-info" style="margin-top: 15px;"><i class="fa fa-info-circle"></i> <strong>Фильтры ввода</strong> не требуют предопределенных вариантов. Пользователи могут ввести любое текстовое значение.</div>');
            }
        } else {
            $('#filter-variable').show();
            $('#input-type-info').remove();
            
            // Show appropriate message for select/checkbox
            var message = '';
            if (filterType == 'select') {
                message = '<strong>Фильтры выбора</strong> позволяют пользователям выбрать один вариант из выпадающего списка.';
            } else if (filterType == 'checkbox') {
                message = '<strong>Чекбокс фильтры</strong> позволяют пользователям выбрать несколько вариантов.';
            }
            
            if (message && $('#type-specific-info').length === 0) {
                $('.alert.alert-info').first().after('<div id="type-specific-info" class="alert alert-success" style="margin-top: 10px;"><i class="fa fa-check-circle"></i> ' + message + '</div>');
                
                // Remove the message after 3 seconds
                setTimeout(function() {
                    $('#type-specific-info').fadeOut(500, function() {
                        $(this).remove();
                    });
                }, 3000);
            }
        }
    });

    // variant block
    $('.add-variant-item').on('click', function() {
        var block = $('#item-form .item-block').last().clone();

        block.find('input').val('');

        $('#item-form').append(block);
    });

    // variant remove block
    $(document).on('click', '.remove-block', function() {
        var block = $(this).parent().parent().parent();
        var cl = block.attr('class');

        if ($('.'+cl).length > 1) {
            block.remove();
        }
    });

    // color
    $('.add-color').on('click', function() {
        var color = $('#color-form .color-block').last().clone();

        color.find('input').val('');
        color.find('.remove-color').show();

        $('#color-form').append(color);
    });

    $(document).on('click', '.remove-color', function() {
        $(this).parent().parent().parent().remove();
    });

    $(document).on('click', '.remove-block', function() {
        var block = $(this).parent().parent().parent();
        var cl = block.attr('class');

        if ($('.'+cl).length > 1) {
            block.remove();
        }
    });
});