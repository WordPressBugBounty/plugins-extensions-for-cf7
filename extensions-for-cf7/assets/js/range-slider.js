(function ($) {
	"use strict";

    $('.wpcf7-extcf7_range_slider').each(function() {
        const type = this?.dataset?.type,
            value = this?.dataset?.default,
            min = this?.dataset?.min,
            max = this?.dataset?.max,
            prefix = this?.dataset?.prefix,
            suffix = this?.dataset?.suffix,
            step = this?.dataset?.step;
        const params = {};
        if (type === "double") {
            params.range = true;
            params.values = value?.includes('-') ? value?.split('-')?.map(Number) : [+value, max];
        } else {
            params.value = +value;
        }
        if (min) { params.min = +min; }
        if (max) { params.max = +max; }
        if (step) { params.step = +step; }

        const amountContainer = $(this).closest('.wpcf7-extcf7-range-slider').find('.wpcf7-extcf7-range-slider-amount');
        
        if(amountContainer && (params.values !== undefined && params.values !== null && params.values !== '')) {
            const minValue = params.values[0];
            const maxValue = params.values[1];
            amountContainer.html(prefix + minValue + suffix + ' - ' + prefix + maxValue + suffix)
        }
        if(amountContainer && (params.value !== undefined && params.value !== null && params.value !== '')) { amountContainer.html(prefix + params.value + suffix) }
        
        $( this.parentElement ).slider({
            ...params,
            slide: function( event, ui ) {
                if(ui?.values) {
                    $(event?.target).find('input.wpcf7-extcf7_range_slider').val( ui.values );
                    const minValue = ui.values[0];
                    const maxValue = ui.values[1];
                    if(amountContainer) {amountContainer.html(prefix + minValue + suffix + ' - ' + prefix + maxValue + suffix)}
                } else {
                    $(event?.target).find('input.wpcf7-extcf7_range_slider').val( ui.value );
                    if(amountContainer) {amountContainer.html(prefix + ui.value + suffix)}
                }
            }
        });
    });
})(jQuery);