/**
 * Copyright (c) 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

'use strict';

module.exports = function(trigger, target, inputDate) {
    $(inputDate).datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        numberOfMonths: 3,
        minDate: 0,
        defaultData: 0
    }).keyup(function(event) {
        if(event.keyCode === 8 || event.keyCode === 46) {
            $.datepicker._clearDate(this);
        }
    });

    function toggleForm() {
        var selectedOptionValue = $(trigger).val();

        if (!selectedOptionValue) {
            $(target).show();

            return;
        }

        $(target).hide();
    }

    toggleForm();

    $(trigger).on('change', toggleForm);
};
