/*======================================================================
 Copyright Project Beehive Forum 2002

 This file is part of Beehive Forum.

 Beehive Forum is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Beehive Forum is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Beehive; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307
 USA
 ======================================================================*/

top.window.beehive = {

    window_options: [
        'toolbox=0',
        'location=0',
        'directories=0',
        'status=0',
        'menubar=0',
        'resizeable=yes',
        'scrollbars=yes'
    ],

    ajax_error: function (message) {

        if ((typeof(console) !== 'undefined') && (typeof(console.log) !== 'undefined')) {
            console.log('AJAX ERROR', message);
        }
    },

    get_resize_width: function () {

        var $max_width = $(this).closest('.max_width[width]');

        if ($max_width.length > 0) {
            return $max_width.prop('width');
        }

        return $(this).find('body').prop('clientWidth');
    },

    reload_frame: function (context, frame_name) {

        $(context).find('frame').each(function () {

            if ($(this).prop('name') == frame_name) {

                $(this).prop('src', $(this).prop('src'));
                return false;
            }

            return top.window.beehive.reload_frame(this.contentDocument, frame_name);
        });
    },

    reload_top_frame: function (context, src) {

        $(context).find('frame').each(function () {

            //noinspection JSUnresolvedVariable
            if ($(this).prop('name') == top.window.beehive.frames.ftop) {

                $(this).prop('src', src);
                return false;
            }

            return top.window.beehive.reload_top_frame(this.contentDocument, src);
        });
    },

    reload_user_font: function (context) {

        $(context).find('frame').each(function () {

            //noinspection JSUnresolvedVariable
            if (!$.inArray($(this).prop('name'), top.window.beehive.frames)) {
                return true;
            }

            return top.window.beehive.reload_user_font(this.contentDocument);
        });

        var $user_font = $(context.head).find('link#user_font');

        $user_font.prop('href', top.window.beehive.forum_path + '/font_size.php?webtag=' + top.window.beehive.webtag + '&_=' + new Date().getTime() / 1000);
    },

    active_editor: null,

    init_editor: function () {

        CKEDITOR.on('dialogDefinition', function (event) {

            var dialogName = event.data.name;
            var dialogDefinition = event.data.definition;

            switch (dialogName) {

                case 'link':

                    dialogDefinition.removeContents('target');
                    dialogDefinition.removeContents('advanced');
                    dialogDefinition.minHeight = 150;
                    break;

                case 'image':

                    dialogDefinition.removeContents('Link');
                    dialogDefinition.removeContents('advanced');
                    break;

                case 'flash':

                    dialogDefinition.removeContents('advanced');
                    dialogDefinition.getContents('properties').remove('menu');
                    dialogDefinition.getContents('properties').remove('scale');
                    dialogDefinition.getContents('properties').remove('align');
                    dialogDefinition.getContents('properties').remove('bgcolor');
                    dialogDefinition.getContents('properties').remove('base');
                    dialogDefinition.getContents('properties').remove('flashvars');
                    dialogDefinition.getContents('properties').remove('allowScriptAccess');
                    dialogDefinition.getContents('properties').remove('allowFullScreen');
                    break;

                case 'allMedias':

                    dialogDefinition.getContents('properties').remove('allowScriptAccess');
                    dialogDefinition.getContents('properties').remove('allowFullScreen');
                    dialogDefinition.getContents('properties').remove('scale');
                    dialogDefinition.getContents('properties').remove('align');
                    dialogDefinition.getContents('properties').remove('play');
                    dialogDefinition.removeContents('advanced');
                    break;
            }
        });

        top.window.beehive.init_editor = function () {
        };
    },

    editor: function () {

        var $editor = $(this);

        var editor_id = $editor.prop('id');

        //noinspection JSUnresolvedVariable
        var skin = top.window.beehive.forum_path + '/styles/' + top.window.beehive.user_style + '/editor/';

        //noinspection JSUnresolvedVariable
        var emoticons = top.window.beehive.forum_path + '/emoticons/' + top.window.beehive.emoticons + '/style.css';

        var contents = skin + 'content.css';

        var toolbar = $editor.hasClass('mobile') ? 'mobile' : 'full';

        //noinspection JSCheckFunctionSignatures
        $('<div id="toolbar">').insertBefore($editor);

        var editor = CKEDITOR.replace(editor_id, {
            allowedContent: true,
            browserContextMenuOnCtrl: true,
            contentsCss: [
                emoticons,
                contents
            ],
            customConfig: '',
            disableNativeSpellChecker: false,
            enterMode: CKEDITOR.ENTER_BR,
            extraPlugins: 'fakeobjects,sharedspace,beehive,youtube,allMedias',
            font_defaultLabel: 'Verdana',
            fontSize_defaultLabel: '12',
            height: $editor.height() - 35,
            language: 'en',
            removePlugins: 'elementspath,contextmenu,tabletools,liststyle,iframe',
            resize_maxWidth: '100%',
            resize_minWidth: '100%',
            shiftEnterMode: CKEDITOR.ENTER_BR,
            skin: 'beehive,' + skin,
            startupFocus: $editor.hasClass('focus'),
            sharedSpaces: {
                top: 'toolbar'
            },
            toolbarCanCollapse: false,
            toolbar_mobile: [
                [
                    'Bold',
                    'Italic',
                    'Underline'
                ]
            ],
            toolbar_full: [
                [
                    'Bold',
                    'Italic',
                    'Underline',
                    'Strike',
                    'Superscript',
                    'Subscript',
                    'JustifyLeft',
                    'JustifyCenter',
                    'JustifyRight',
                    'NumberedList',
                    'BulletedList',
                    'Indent',
                    'Code',
                    'Quote',
                    'Spoiler',
                    'HorizontalRule',
                    'Image',
                    'Youtube',
                    'Flash',
                    'Link',
                    'allMedias'
                ],
                [
                    'Font',
                    'FontSize',
                    'TextColor',
                    'Source'
                ]
            ],
            toolbar: toolbar,
            width: $editor.width()
        });

        top.window.beehive.init_editor();

        if (editor) {

            editor.on('focus', function (event) {
                if (event.editor) {
                    top.window.beehive.active_editor = event.editor;
                }
            });
        }

        if ($editor.hasClass('quick_reply')) {

            var $post_button = $editor.closest('form').find('input#post');

            if (editor) {

                editor.on('key', function (event) {

                    if (event.data.keyCode != CKEDITOR.CTRL + 13) {
                        return;
                    }

                    if (event.editor.getData().length == 0) {
                        return;
                    }

                    $editor.val(event.editor.getData());

                    $post_button.click();
                });
            }
        }
    },

    mobile_version: false,

    use_mover_spoiler: 'N',

    forum_path: null,

    lang: {}
};

$.ajaxSetup({

    cache: true,

    error: function (data) {
        top.window.beehive.ajax_error(data);
    }
});

$(top.window.beehive).bind('init', function () {

    var frame_resize_timeout;

    top.window.beehive.mobile_version = $('body#mobile').length > 0;

    $('form[method="get"]').append(
        $('<input type="hidden" name="_">').val((new Date()).getTime())
    );

    $('.move_up_ctrl_disabled, .move_down_ctrl_disabled').bind('click', function () {
        return false;
    });

    var $body = $('body').on('click', 'a.popup', function () {

        var class_names = $(this).prop('class').split(' ');

        var window_options = top.window.beehive.window_options;

        var dimensions;

        for (var key = 0; key < class_names.length; key++) {

            dimensions = /^([0-9]+)x([0-9]+)$/.exec(class_names[key]);

            if (dimensions && dimensions[1] && dimensions[2]) {

                window_options.unshift('width=' + dimensions[1], 'height=' + dimensions[2]);
            }
        }

        window.open($(this).prop('href'), $(this).prop('id'), window_options.join(','));

        return false;
    });

    $('input#close_popup').bind('click', function () {
        window.close();
    });

    $('select.user_in_thread_dropdown').bind('change', function () {
        $('input[name="to_radio"][value="in_thread"]').prop('checked', true);
    });

    $('select.recent_user_dropdown').bind('change', function () {
        $('input[name="to_radio"][value="recent"]').prop('checked', true);
    });

    $('select.friends_dropdown').bind('change', function () {
        $('input[name="to_radio"][value="friends"]').prop('checked', true);
    });

    $('input.post_to_others').bind('focus', function () {
        $('input[name="to_radio"][value="others"]').prop('checked', true);
    });

    $('input#toggle_all').bind('change', function () {
        $(this).closest('form').find('input:checkbox').prop('checked', $(this).is(':checked'));
    });

    $body.on('click', 'a.font_size_larger, a.font_size_smaller', function () {

        var $this = $(this);

        var $parent = $this.closest('td');

        //noinspection JSUnresolvedVariable
        if (top.window.beehive.uid == 0) {
            return true;
        }

        $.ajax({
            cache: true,
            data: {
                webtag: top.window.beehive.webtag,
                ajax: 'true',
                action: $this.prop('class'),
                msg: $this.data('msg')
            },
            dataType: 'json',
            url: top.window.beehive.forum_path + '/ajax.php',
            success: function (data) {

                try {

                    $parent.html(data.html);

                    top.window.beehive.font_size = data.font_size;

                    top.window.beehive.reload_user_font(top.document);

                    $(top.document).find('frameset#index').prop('rows', '60,' + Math.max(top.window.beehive.font_size * 2, 22) + ',*');

                } catch (exception) {

                    top.window.beehive.ajax_error(exception);
                }
            }
        });

        return false;
    });

    $('#preferences_updated').each(function () {

        //noinspection JSUnresolvedVariable
        top.window.beehive.reload_frame(top.document, top.window.beehive.frames.fnav);

        //noinspection JSUnresolvedVariable
        top.window.beehive.reload_frame(top.document, top.window.beehive.frames.left);

        //noinspection JSUnresolvedVariable
        top.window.beehive.reload_top_frame(top.document, top.window.beehive.top_frame);
    });

    $('input#print').bind('click', function () {
        window.print();
    });

    $('a.button').bind('mousedown', function () {
        $(this).css('border', '1px inset');
    }).bind('mouseup mouseout', function () {
        $(this).css('border', '1px outset');
    });

    if ($body.hasClass('window_title')) {
        top.document.title = document.title;
    }

    $(window).bind('resize', function () {

        var frame_name = $(this).prop('name');

        //noinspection JSUnresolvedVariable
        if ((frame_name != top.window.beehive.frames.left) && (frame_name != top.window.beehive.frames.pm_folders)) {
            return;
        }

        //noinspection JSUnresolvedVariable
        if (top.window.beehive.uid == 0) {
            return;
        }

        window.clearTimeout(frame_resize_timeout);

        frame_resize_timeout = window.setTimeout(function () {

            $.ajax({

                cache: true,

                data: {
                    webtag: top.window.beehive.webtag,
                    ajax: true,
                    action: 'frame_resize',
                    size: Math.max(100, this.innerWidth)
                },

                url: top.window.beehive.forum_path + '/ajax.php'
            });

        }, 500);
    });

    $('.toggle_button').bind('click', function () {

        var $button = $(this);

        var $element = $('.' + $button.prop('id'));

        if ($element.is(':visible')) {

            $element.slideUp(150, function () {

                $button.removeClass('hide').addClass('show');

                $.ajax({

                    cache: true,

                    data: {
                        webtag: top.window.beehive.webtag,
                        ajax: true,
                        action: $button.prop('id'),
                        display: 'false'
                    },

                    url: top.window.beehive.forum_path + '/ajax.php'
                });
            });

        } else {

            $element.slideDown(150, function () {

                $button.removeClass('show').addClass('hide');

                $.ajax({

                    cache: true,

                    data: {
                        webtag: top.window.beehive.webtag,
                        ajax: true,
                        action: $button.prop('id'),
                        display: 'true'
                    },

                    url: top.window.beehive.forum_path + '/ajax.php',

                    success: function () {
                        $element.find('textarea.editor:visible').each(top.window.beehive.editor);
                    }
                });
            });
        }

        return false;
    });

    $('textarea.editor:visible').each(top.window.beehive.editor);

    $('input, textarea').placeholder();

    //noinspection JSUnresolvedVariable
    if (top.window.beehive.show_share_links == 'Y') {

        $.getScript(document.location.protocol + '//apis.google.com/js/plusone.js');

        $.getScript(document.location.protocol + '//platform.twitter.com/widgets.js');

        $.getScript(document.location.protocol + '//connect.facebook.net/en_US/all.js');
    }
});