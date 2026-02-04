$(function() {
    $('.checkbox__all').on('change', function() {
        if ($(this).is(':checked')) {
            $('.checkbox__all__white').attr('checked', 'checked');
            $('.accept__check').show();
        } else {
            $('.checkbox__all__white').removeAttr('checked');
            $('.accept__check').hide();
        }
    });

    $(document).on('change', '#item-block input[type=checkbox]', function() {
        var check = false;

        $("#item-block input[type=checkbox]").each(function() {
            if ($(this).is(':checked')) {
                check = true;
            }
        });

        if (check === true) {
            $('.accept__check').show();
        } else {
            $('.accept__check').hide();
        }
    });

    var url = window.location.href.split('?');
    if (url[1]) {
        url = url[1].split('=');
        var type = url[1];

        if (type != 'inactive') {
            url = type.split('&');
            type = url[0];
        }
    }

    $(document).on('change', '.active__select1', function() {
        if (type == 'inactive') {
            location.href="/account/index?type=inactive&sort="+$(this).val();
        } else {
            location.href="/account/index?sort="+$(this).val();
        }
    });

    $(document).on('click', '.photo__delete', function() {
        var id = $(this).parent().attr('class').split(' ');
        id = id[1].split('_');
        id = id[1];

        $('.t_'+id).remove();

        $('.photo__col__input').hide();
        $('.photo__col__input').last().show();
    });
    $('.agreement').on('change', function() {
        if ($(this).is(':checked')) {
            $('#create-ad').show();
        } else {
            $('#create-ad').hide();
        }
    });

    $('.categories__link').on('click', function() {
        var id = $(this).attr('class').split(' ');
        id = id[1].split('-');
        id = id[1];
  
        $('#'+id).toggle();
    });

    $('.main-category').on('click', function() {
        $('.subcategory').hide();
        var id = $(this).attr('data');
        $('#subcategory-'+id).show();
      });

    $('.favorite').on('click', function() {
        var id = $(this).attr('data');

        $.ajax({
            url: '/ads/set-favorite',
            type: 'post',
            data: {'id':id},
            success: function(data) {
                if (data) {
                    if (data['result'] == true) {
                        $('.favorite-'+id).find('i').attr('class', 'fas fa-star');
                    } else {
                        $('.favorite-'+id).find('i').attr('class', 'far fa-star');
                    }

                    $('#favorite-count').html(data['count']);
                    $('#favorite-count-menu').html(data['count']);
                }
            }
        });

        return false;
    });

    $('#ads-main-category').on('change', function() {
		$('.subcategory').hide();
		$('#category-'+$(this).val()).show();

        $('.filter-block').hide();
        $('#filter-block-'+$(this).val()).show();
	});

    $(document).on('change', '#language-picker-select', function() {
        return location.href = '/main/change-language?id='+$(this).val();
    });
});

const addPhotoCart = document.querySelectorAll(".photo__col__input")
function addPhoto(){
    const label = document.createElement("label")
    label.setAttribute("for", "file-input")
    label.setAttribute("class", "photo__label")
    label.innerHTML = "+"
    for(let i=0;i<addPhotoCart.length;i++){
        console.log(addPhotoCart[i])
    }
}
addPhoto()