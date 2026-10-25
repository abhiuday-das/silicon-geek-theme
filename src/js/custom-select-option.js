jQuery(document).ready(function ($) {
    //CODE FOR BACK TO TOP BUTTON START
    jQuery('body').on("click", '.select-selected', function (e) {
        e.stopPropagation();
        closeAllSelect(this);
        this.nextSibling.classList.toggle("select-hide");
        this.classList.toggle("select-arrow-active");
    });
    jQuery('body').on("click", '.select-items div', function (e) {
        e.stopPropagation();
        jQuery(this).parent(".select-items").toggleClass("select-hide");
        var y, i, k, s, h;
        s = this.parentNode.parentNode.getElementsByTagName("select")[0];
        h = this.parentNode.previousSibling;
        for (i = 0; i < s.length; i++) {
            if (s.options[i].innerHTML == this.innerHTML) {
                s.selectedIndex = i;
                h.innerHTML = this.innerHTML;
                y = this.parentNode.getElementsByClassName("same-as-selected");
                for (k = 0; k < y.length; k++) {
                    y[k].removeAttribute("class");
                }
                this.setAttribute("class", "same-as-selected");
                jQuery(this).closest(".ginput_container").siblings(".gfield_label").addClass("float_label")
                break;
            }
        }
    });
    jQuery('body').on("keydown", ".dropdown-select .ginput_container.ginput_container_select", function (e) {
        var has_parent_validation = jQuery(this).parent().parent().parent().parent().parent().hasClass("gform_validation_error");
        console.log(has_parent_validation);
        this.classList.toggle("select-arrow-active");
        var indexes = jQuery(this).children(".select-items").children("div").length;
        var new_index = 0;
        s = jQuery(this).children("select.gfield_select");
        //keydown
        var selected_element = '';
        var current_text = "";
        var current_text = "";
        if (e.which === 40) {
            e.preventDefault();
            e.stopPropagation();
            e.stopPropagation();
            current_text = jQuery(this).children(".select-selected").text();
            for (i = 0; i < indexes; i++) {
                if (current_text === jQuery(this).children(".select-items").children().eq(i).text()) {
                    jQuery(this).children(".select-items").children().eq(i).removeClass("same-as-selected");
                    new_index = i + 1;
                }
            }
            if (new_index >= indexes) {
                new_index = indexes - 1;
            }
            selected_index = new_index + 1;
            jQuery(this).children("select.gfield_select").children().eq(selected_index).attr('selected', "selected");
            selected_element = jQuery(this).children(".select-items").children().eq(new_index).text();
            jQuery(this).children(".select-items").children().eq(new_index).addClass("same-as-selected");
            jQuery(this).children(".select-selected").text(selected_element);
        } else if (e.which === 38) {
            e.preventDefault();
            e.stopPropagation();
            //keyup
            current_text = jQuery(this).children(".select-selected").text();
            for (i = indexes; i >= 0; i--) {
                if (current_text === jQuery(this).children(".select-items").children().eq(i).text()) {
                    jQuery(this).children(".select-items").children().eq(i).removeClass("same-as-selected");
                    new_index = i - 1;
                }
            }
            if (new_index < 0) {
                new_index = 0;
            }
            jQuery(this).children("select.gfield_select").children().eq(new_index + 1).attr('selected', "selected");
            selected_element = jQuery(this).children(".select-items").children().eq(new_index).text();
            jQuery(this).children(".select-selected").text(selected_element);
            jQuery(this).children(".select-items").children().eq(new_index).addClass("same-as-selected");
        }
    });
});
jQuery(document).on('gform_post_render', function (event, form_id, current_page) {
    if (jQuery(".gform_validation_error .dropdown-select").find("select").val() != "") {
        jQuery(".gform_validation_error .dropdown-select").find(".gfield_label").addClass("float_label");
    }
});