jQuery(function($){

    // Detect unsaved changes

    var wmhk_ajax_queue = 0;

    $(window).bind("beforeunload", function(event){
        if(wmhk_ajax_queue > 0) return wmhk_language.unsaved_changes;
    });

    // Submit form

    $(document).on("submit", ".wmhk-form", function(event){

        var $form = $(this),
            $result = $form.find(".wmhk-result");

        // Show loading spiner

        $form.addClass("wmhk-loading");

        // Hide message

        $result.removeClass("wmhk-show wmhk-error");

        // Set ajax queue

        wmhk_ajax_queue++;

        // Ajax submit

        $form.ajaxSubmit({
            type: "POST",
            dataType: "json",
            success: function(data){

                // Success

                if(data.success){
                    $result.addClass("wmhk-show");
                    $result.html(wmhk_language.success);
                }

                // Error

                if(typeof(data.error)!=="undefined" && data.error > 0){
                    $result.addClass("wmhk-show wmhk-error");
                    $result.html(wmhk_language.request_error);
                }

                // Hide loading spiner

                $form.removeClass("wmhk-loading");

                // Unset ajax queue

                wmhk_ajax_queue--;

            }, error: function(){
                // Show error
                $result.addClass("wmhk-error");
                $result.html(wmhk_language.request_error);
                // Unset ajax queue
                wmhk_ajax_queue--;
            }
        });

        event.preventDefault();

    });

    // Toggle / checkbox

    $(document).on("click", ".wmhk-toggle", function(e){

        e.preventDefault();
        e.stopPropagation();

        let $el = $(this).find("input[type='checkbox']");

        if($el.prop("checked")) $el.prop("checked", false).trigger("change");
        else $el.prop("checked", true).trigger("change");

    });

    $(document).on("change", ".wmhk-toggle input[type='checkbox']", function(){

        var $this = $(this),
            $parent = $this.closest(".wmhk-toggle"),
            $label = $parent.find(".wmhk-toggle-label");

        if($this.prop("checked")){
            $label.text($parent.attr("data-text-on"));
            $parent.addClass("checked");
        }else{
            $label.text($parent.attr("data-text-off"));
            $parent.removeClass("checked");
        }

    });

    $(".wmhk-toggle input[type='checkbox']").trigger("change");

    // Advanced mode

    $(document).on("change", "#wmhk_advanced_mode", function(){
        if($(this).prop("checked")){
            $(".wmhk-form").addClass("-advanced");
        }else{
            $(".wmhk-form").removeClass("-advanced");
        }
    });

});