/*
 * iLockout Security Suite
 * (c) iLockout INC.
 */
 function getSourceFile(fileName, fileHash) {
    jQuery.post(ajaxurl, {
        action: 'ilockout_get_file_source',
        filename: fileName,
        hash: fileHash
    }, function (response) {
        if (response) {
            if (response.err) {
                jQuery("#source-dialog").html('<p><b>An error occured.</b> ' + response.err + '</p>');
            } else {
				jQuery("#source-dialog").html('<pre class="ilockout-source"></pre>');
                jQuery('pre', "#source-dialog").text(response.source);
            }
        } else {
            alert('An undocumented error occured. The page will reload.');
            window.location.reload();
        }
    }, 'json');
} //end function


jQuery(document).ready(function ($) {
    // init tabs
    $("#ilocktabs").tabs();
	
    $('a.ilockout-show-source').click(function () {
		$("#source-dialog").html("");
		getSourceFile($(this).attr("data-file"), $(this).attr("data-hash"));
		$("#source-dialog").dialog('option', {
            title: 'File source: ' + $(this).attr('data-file'),
        }).dialog('open');
        return false;
    });


    $('#source-dialog').dialog({
		'autoOpen': false,
		'modal': true,
		'dialogClass': 'wp-dialog',
		'resizeable': false,
		'z-index': 9999,
		'width': 800,
		'height': 600,
		'hide': 'fade',
        'show': 'fade',
        'autoOpen': false,
        'closeOnEscape': true,
    });
	
    // scan files
    $('#fix_readme').removeAttr('disabled').click(function () {
        var data = {
            action: 'ilockout_fix_readme'
        };
        $(this).attr('disabled', 'disabled')
            .val('Fixing...');
        $.blockUI({
            message: 'iLockout is fixing this issue for you. Please wait for a moment'
        });
        $.post(ajaxurl, data, function (response) {
            if (response != 1) {
                alert('Undocumented error. Page will automatically reload.');
                window.location.reload();
            } else {
                window.location.reload();
            }
        });
    });
    $('#fix_username_admin').after("<div class='change-uname' style='display: none;'><input type='text' value='Enter username' name='new_user' size='15' style='color: #dedede; font-style: italic;'/><a id='fix_username_save' class='button-primary' style='margin-top: 3px;'>Submit</a></div>");

    $('input[name="new_user"]').focus(function () {
        $(this).val('');
        $(this).css({
            "color": "#333",
            "font-style": "normal"
        });
    }).blur(function () {
        if ($(this).val() === '')
            $(this).val('Enter username');
        $(this).css({
            "color": "#dedede",
            "font-style": "italic"
        });
    });

    $('#fix_username_admin').click(function () {
        if ($('.change-uname').is(":hidden")) {
            $('.change-uname').slideDown("slow");
        } else {
            $('.change-uname').hide();
        }
        $(this).hide();
    });

    $('#fix_username_save').removeAttr('disbaled').click(function () {
        var new_user = $('input[name="new_user"]').val();
        var data = {
            action: 'ilockout_fix_username_admin',
            new_user: new_user
        };
        $(this).attr('disabled', 'disabled')
            .val('Fixing...');
        $.blockUI({
            message: 'iLockout is fixing this issue for you. Please wait for a moment'
        });
        $.post(ajaxurl, data, function (response) {
            if (response != '1') {
                alert('Undocumented error. Page will automatically reload.');
                window.location.reload();
            } else {
                window.location.reload();
            }
        });
    });

    $('#fix_user_id').removeAttr('disabled').click(function () {
        var data = {
            action: 'ilockout_fix_user_id'
        };
        $(this).attr('disabled', 'disabled').val('Fixing...');
        $.blockUI({
            message: 'iLockout is fixing this issue for you. Please wait for a moment'
        });
        $.post(ajaxurl, data, function (response) {
            if (response != '1') {
                alert('Undocumented error. Page will automatically reload.');
                window.location.reload();
            } else {
                window.location.reload();
            }
        });
    });


    $('#ilockout_scan_button').removeAttr('disabled').click(function () {
        var data = {
            action: 'ilockout_scan'
        };

        $(this).attr('disabled', 'disabled')
            .val('Scanning files, please wait!');
        $.blockUI({
            message: 'iLockout Scanner is scanning for your files. This process will be done soon. Please wait for a moment.'
        });

        $.post(ajaxurl, data, function (response) {
            if (response != '1') {
                alert('Undocumented error. Page will automatically reload');
                window.location.reload();
            } else {
                window.location.reload();
            }
        });
    }); // run tests

    $('#ilockout_db_scan_button').removeAttr('disabled').click(function () {
        var data = {
            action: 'ilockout_db_scan'
        };

        $(this).attr('disabled', 'disabled')
            .val('Scanning database, please wait!');
        $.blockUI({
            message: 'iLockout Scanner is scanning for your database. This process will be done soon. Please wait for a moment'
        });

        $.post(ajaxurl, data, function (response) {
            if (response != '1') {
                alert('Undocumented error. Page will automatically reload');
                window.location.reload();
            } else {
                window.location.reload();
            }
        });
    });
}); // onload

