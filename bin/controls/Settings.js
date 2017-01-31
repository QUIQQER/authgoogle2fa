/**
 * Registration of codes for Google Authenticator QUIQQER plugin
 *
 * @module package/quiqqer/authgoogle2fa/bin/controls/Settings
 * @author www.pcsg.de (Patrick Müller)
 *
 * @require qui/QUI
 * @require qui/controls/Control
 * @require qui/controls/buttons/Button
 * @requrie Ajax
 * @require Locale
 * @require css!package/quiqqer/authgoogle2fa/bin/controls/Settings.css
 *
 */
define('package/quiqqer/authgoogle2fa/bin/controls/Settings', [

    'qui/controls/Control',
    'qui/controls/windows/Confirm',
    'qui/controls/buttons/Button',
    'qui/controls/loader/Loader',

    'controls/grid/Grid',

    'Mustache',
    'Ajax',
    'Locale',

    'text!package/quiqqer/authgoogle2fa/bin/controls/KeyData.html',
    'css!package/quiqqer/authgoogle2fa/bin/controls/Settings.css'

], function (QUIControl, QUIConfirm, QUIButton, QUILoader, Grid, Mustache,
             QUIAjax, QUILocale, keyDataTemplate) {
    "use strict";

    var lg = 'quiqqer/authgoogle2fa';
    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/authgoogle2fa/bin/controls/Settings',

        Binds: [
            '$onInject',
            '$onRefresh',
            '$onCreate',
            '$onResize',
            'refresh',
            '$listRefresh',
            '$generateKey',
            '$showKey',
            '$deleteKeys'
        ],

        options: {
            uid: false
        },

        initialize: function (options) {
            this.setAttribute('title', QUILocale.get(lg, 'passwords.panel.title'));

            this.parent(options);

            this.addEvents({
                onInject: this.$onInject,
                onResize: this.$onResize
            });

            this.Loader         = new QUILoader();
            this.$GridContainer = null;
            this.$Grid          = null;
        },

        /**
         * event on DOMElement creation
         */
        create: function () {
            var self = this;

            this.$Elm = new Element('div', {
                'class': 'quiqqer-auth-google2fa-register'
            });

            this.Loader.inject(this.$Elm);

            // content
            this.$GridContainer = new Element('div', {
                'class': 'quiqqer-auth-google2fa-register-grid-container'
            }).inject(this.$Elm);

            var GridContainer = new Element('div', {
                'class': 'quiqqer-auth-google2fa-register-grid'
            }).inject(this.$GridContainer);

            this.$Grid = new Grid(GridContainer, {
                buttons          : [{
                    name     : 'generateKey',
                    text     : QUILocale.get(lg, 'controls.settings.table.btn.generateKey'),
                    textimage: 'fa fa-plus',
                    events   : {
                        onClick: self.$generateKey
                    }
                }, {
                    name     : 'showKey',
                    text     : QUILocale.get(lg, 'controls.settings.table.btn.showKey'),
                    textimage: 'fa fa-key',
                    events   : {
                        onClick: function () {
                            self.$showKey(self.$Grid.selected[0]);
                        }
                    }
                }, {
                    name     : 'delete',
                    text     : QUILocale.get(lg, 'controls.settings.table.btn.delete'),
                    textimage: 'fa fa-trash',
                    events   : {
                        onClick: self.$deleteKeys
                    }
                }],
                pagination       : false,
                serverSort       : false,
                multipleSelection: true,
                columnModel      : [{
                    header   : QUILocale.get('quiqqer/system', 'title'),
                    dataIndex: 'title',
                    dataType : 'string',
                    width    : 150
                }, {
                    header   : QUILocale.get('quiqqer/system', 'createdate'),
                    dataIndex: 'created',
                    dataType : 'text',
                    width    : 250
                }]
            });

            this.$Grid.addEvents({
                onDblClick: function () {
                    self.$showKey(self.$Grid.selected[0]);
                },
                onClick   : function () {
                    var TableButtons = self.$Grid.getAttribute('buttons');

                    if (self.$Grid.getSelectedData().length == 1) {
                        TableButtons.showKey.enable();
                    } else {
                        TableButtons.showKey.disable();
                    }

                    TableButtons.delete.enable();
                },
                onRefresh : this.refresh
            });

            return this.$Elm;
        },

        /**
         * Event: onInject
         */
        $onInject: function () {

            console.log(this.getAttribute('uid'));
            console.log(this.getElm().get('data-qui-options-uid'));

            this.resize();
            this.refresh();
        },

        /**
         * Event: onRefresh
         */
        $onRefresh: function () {
            this.refresh();
        },

        /**
         * Event: onResize
         */
        $onResize: function () {
            if (!this.$GridContainer) {
                return;
            }

            var size = this.$GridContainer.getSize();

            //this.$Grid.setHeight(size.y);
            this.$Grid.setHeight(400);
            this.$Grid.resize();
        },

        /**
         * Refresh key list
         *
         * @return {Promise}
         */
        refresh: function () {
            var self = this;

            this.Loader.show();

            var TableButtons = self.$Grid.getAttribute('buttons');

            TableButtons.delete.disable();
            TableButtons.showKey.disable();

            return new Promise(function (resolve, reject) {
                QUIAjax.get(
                    'package_quiqqer_authgoogle2fa_ajax_getKeys',
                    function (keys) {
                        self.$Grid.setData({
                            data : keys,
                            page : 1,
                            total: 1
                        });

                        self.Loader.hide();
                        resolve();
                    }, {
                        'package': 'quiqqer/authgoogle2fa',
                        'userId' : self.getAttribute('uid'),
                        onError  : reject
                    }
                );
            });
        },

        /**
         * Generate a new google authenticator key
         */
        $generateKey: function () {
            var self = this;

            // open popup
            var Popup = new QUIConfirm({
                title             : QUILocale.get(
                    lg, 'controls.settings.generatekey.title'
                ),
                maxHeight         : 200,
                maxWidth          : 350,
                icon              : 'fa fa-plus',
                backgroundClosable: true,

                // buttons
                buttons         : true,
                titleCloseButton: true,
                content         : false,
                events          : {
                    onOpen  : function () {
                        Popup.getContent().set(
                            'html',
                            '<label class="quiqqer-auth-google2fa-register-generatekey">' +
                            '<span>' + QUILocale.get(lg, 'controls.settings.generatekey.title.label') + '</span>' +
                            '<input type="text"/>' +
                            '</label>'
                        );

                        var Input = Popup.getContent().getElement('input');
                        Input.focus();

                        Input.addEvents({
                            keyup: function (event) {
                                if (event.code === 13) {
                                    Popup.submit();
                                    Input.blur();
                                }
                            }
                        });
                    },
                    onSubmit: function () {
                        var Input = Popup.getContent().getElement('input');
                        var val   = Input.value.trim();

                        if (val == '') {
                            Input.value = '';
                            Input.focus();
                            return;
                        }

                        Popup.Loader.show();

                        QUIAjax.post(
                            'package_quiqqer_authgoogle2fa_ajax_generateKey',
                            function (success) {
                                Popup.Loader.hide();

                                if (success) {
                                    Popup.close();

                                    self.refresh().then(function () {
                                        self.$showKey(self.$Grid.getData().length - 1);
                                    });
                                }
                            }, {
                                'package': 'quiqqer/authgoogle2fa',
                                title    : val,
                                userId   : self.getAttribute('uid')
                            }
                        )
                    }
                }
            });

            Popup.open();
        },

        /**
         * Show access code for google authenticator account
         */
        $showKey: function (row) {
            var self = this;
            var KeyData;
            var Row  = this.$Grid.getDataByRow(row);

            var Popup = new QUIConfirm({
                title             : QUILocale.get(
                    lg, 'controls.settings.showkey.title'
                ),
                maxHeight         : 720,
                maxWidth          : 650,
                icon              : 'fa fa-key',	// {false|string} [optional] icon of the window
                backgroundClosable: true, // {bool} [optional] closes the window on click? standard = true

                // buttons
                buttons         : false, // {bool} [optional] show the bottom button line
                //closeButtonText : Locale.get('qui/controls/windows/Popup', 'btn.close'),
                titleCloseButton: true,  // {bool} show the title close button
                content         : false,
                events          : {
                    onOpen: function () {
                        var Content  = Popup.getContent();
                        var lgPrefix = 'controls.settings.showkey.template.';

                        Content.set(
                            'html',
                            Mustache.render(keyDataTemplate, {
                                key              : KeyData.key,
                                createUser       : KeyData.createUser,
                                createDate       : KeyData.createDate,
                                tableHeader      : QUILocale.get(lg, lgPrefix + 'tableHeader', {
                                    title: Row.title
                                }),
                                labelQrCode      : QUILocale.get(lg, lgPrefix + 'labelQrCode'),
                                labelKey         : QUILocale.get(lg, lgPrefix + 'labelKey'),
                                labelCreateUser  : QUILocale.get(lg, lgPrefix + 'labelCreateUser'),
                                labelCreateDate  : QUILocale.get(lg, lgPrefix + 'labelCreateDate'),
                                labelRecoveryKeys: QUILocale.get(lg, lgPrefix + 'labelRecoveryKeys')
                            })
                        );

                        // QR Code
                        new Element('img', {
                            src: KeyData.qrCode
                        }).inject(
                            Content.getElement(
                                '.quiqqer-auth-google2fa-register-showkey-qrcode'
                            )
                        );

                        // Recovery keys
                        var RecoveryListElm = Content.getElement(
                            '.quiqqer-auth-google2fa-register-showkey-recoverykeys ul'
                        );

                        for (var i = 0, len = KeyData.recoveryKeys.length; i < len; i++) {
                            new Element('li', {
                                html: KeyData.recoveryKeys[i]
                            }).inject(RecoveryListElm);
                        }
                    }
                }
            });

            this.Loader.show();

            QUIAjax.get(
                'package_quiqqer_authgoogle2fa_ajax_getKey',
                function (Result) {
                    KeyData = Result;
                    Popup.open();
                    self.Loader.hide();
                }, {
                    'package': 'quiqqer/authgoogle2fa',
                    title    : Row.title,
                    userId   : self.getAttribute('uid')
                }
            );
        },

        /**
         * Delete key(s)
         */
        $deleteKeys: function () {
            var self   = this;
            var data   = this.$Grid.getSelectedData();
            var titles = [];

            for (var i = 0, len = data.length; i < len; i++) {
                titles.push(data[i].title);
            }

            // open popup
            var Popup = new QUIConfirm({
                title             : QUILocale.get(
                    lg, 'controls.settings.deleteKeys.title'
                ),
                maxHeight         : 300,
                maxWidth          : 500,
                icon              : 'fa fa-trash',
                backgroundClosable: true,

                // buttons
                buttons         : true,
                titleCloseButton: true,
                content         : false,
                events          : {
                    onOpen  : function () {
                        Popup.getContent().set(
                            'html',
                            QUILocale.get(lg, 'controls.settings.deleteKeys.info', {
                                titles: titles.join('<br/>')
                            })
                        );
                    },
                    onSubmit: function () {
                        Popup.Loader.show();

                        QUIAjax.post(
                            'package_quiqqer_authgoogle2fa_ajax_deleteKeys',
                            function (success) {
                                Popup.close();
                                self.refresh();
                            }, {
                                'package': 'quiqqer/authgoogle2fa',
                                titles   : JSON.encode(titles),
                                userId   : self.getAttribute('uid')
                            }
                        )
                    }
                }
            });

            Popup.open();
        }
    });
});