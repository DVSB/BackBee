var bb = (bb) ? bb : {};

if ('undefined' == typeof($.md5)) {
    $.extend($, {
        md5: function(str) {
            //misc functions
            var add = function(x, y) {
                var lsw = (x & 0xFFFF) + (y & 0xFFFF);
                var msw = (x >>> 16) + (y >>> 16) + (lsw >>> 16);
                return (msw << 16) | (lsw & 0xFFFF);
            }
			
            var rol = function(x, y){
                return (x << y) | (x >>> (32 - y));
            }
			
            var hex = function(abcd) {
                var hex_string = "";
                var hex_buffer = "0123456789abcdef";
				
                for(var i = 0; i < abcd.length; i++) for(var j = 0; j < 32; j += 8)
                    hex_string += hex_buffer.charAt( (abcd[i] >> (j+4)) & 0xF) + hex_buffer.charAt( (abcd[i] >> j) & 0xF);
				
                return hex_string;
            }
			
            //md5 functions
            var xx = function(q, a, b, x, s, ac) {
                return add(rol(add(add(a, q), add(x, ac)), s),b);
            }
			
            var ff = function(a, b, c, d, x, s, ac) {
                return xx((b & c) | ((~b) & d), a, b, x, s, ac);
            }
			
            var gg = function(a, b, c, d, x, s, ac) {
                return xx((b & d) | (c & (~d)), a, b, x, s, ac);
            }
			
            var hh = function(a, b, c, d, x, s, ac) {
                return xx(b ^ c ^ d, a, b, x, s, ac);
            }
			
            var ii = function(a, b, c, d, x, s, ac) {
                return xx(c ^ (b | (~d)), a, b, x, s, ac);
            }
			
            //STR digest buffer
			
            str = str.toString();
			
            var i;
            var buffer = [];
            var char_size = 8;
            var char_mask = (1 << char_size) - 1;
			
            for(i = 0; i < str.length * char_size; i += char_size)
                buffer[i >> 5] |= (str.charCodeAt(i / char_size) & char_mask) << (i & 0x1F);
			
            //str digest padding
			
            buffer[i >> 5] |= 0x80 << (i & 0x1F);
            buffer[(((i + 64) >>> 5) & ~0xF) + 14] = i;
			
            //str digest loop
			
            var a = 0x67452301;
            var b = 0xEFCDAB89;
            var c = 0x98BADCFE;
            var d = 0x10325476;
			
            for(i = 0; i < buffer.length; i += 16) {
                var temp_a = a;
                var temp_b = b;
                var temp_c = c;
                var temp_d = d;
				
                a = ff(a, b, c, d, buffer[i+ 0],  7, 0xD76AA478);
                d = ff(d, a, b, c, buffer[i+ 1], 12, 0xE8C7B756);
                c = ff(c, d, a, b, buffer[i+ 2], 17, 0x242070DB);
                b = ff(b, c, d, a, buffer[i+ 3], 22, 0xC1BDCEEE);
                a = ff(a, b, c, d, buffer[i+ 4],  7, 0xF57C0FAF);
                d = ff(d, a, b, c, buffer[i+ 5], 12, 0x4787C62A);
                c = ff(c, d, a, b, buffer[i+ 6], 17, 0xA8304613);
                b = ff(b, c, d, a, buffer[i+ 7], 22, 0xFD469501);
                a = ff(a, b, c, d, buffer[i+ 8],  7, 0x698098D8);
                d = ff(d, a, b, c, buffer[i+ 9], 12, 0x8B44F7AF);
                c = ff(c, d, a, b, buffer[i+10], 17, 0xFFFF5BB1);
                b = ff(b, c, d, a, buffer[i+11], 22, 0x895CD7BE);
                a = ff(a, b, c, d, buffer[i+12],  7, 0x6B901122);
                d = ff(d, a, b, c, buffer[i+13], 12, 0xFD987193);
                c = ff(c, d, a, b, buffer[i+14], 17, 0xA679438E);
                b = ff(b, c, d, a, buffer[i+15], 22, 0x49B40821);
				
                a = gg(a, b, c, d, buffer[i+ 1],  5, 0xF61E2562);
                d = gg(d, a, b, c, buffer[i+ 6],  9, 0xC040B340);
                c = gg(c, d, a, b, buffer[i+11], 14, 0x265E5A51);
                b = gg(b, c, d, a, buffer[i+ 0], 20, 0xE9B6C7AA);
                a = gg(a, b, c, d, buffer[i+ 5],  5, 0xD62F105D);
                d = gg(d, a, b, c, buffer[i+10],  9, 0x02441453);
                c = gg(c, d, a, b, buffer[i+15], 14, 0xD8A1E681);
                b = gg(b, c, d, a, buffer[i+ 4], 20, 0xE7D3FBC8);
                a = gg(a, b, c, d, buffer[i+ 9],  5, 0x21E1CDE6);
                d = gg(d, a, b, c, buffer[i+14],  9, 0xC33707D6);
                c = gg(c, d, a, b, buffer[i+ 3], 14, 0xF4D50D87);
                b = gg(b, c, d, a, buffer[i+ 8], 20, 0x455A14ED);
                a = gg(a, b, c, d, buffer[i+13],  5, 0xA9E3E905);
                d = gg(d, a, b, c, buffer[i+ 2],  9, 0xFCEFA3F8);
                c = gg(c, d, a, b, buffer[i+ 7], 14, 0x676F02D9);
                b = gg(b, c, d, a, buffer[i+12], 20, 0x8D2A4C8A);
				
                a = hh(a, b, c, d, buffer[i+ 5],  4, 0xFFFA3942);
                d = hh(d, a, b, c, buffer[i+ 8], 11, 0x8771F681);
                c = hh(c, d, a, b, buffer[i+11], 16, 0x6D9D6122);
                b = hh(b, c, d, a, buffer[i+14], 23, 0xFDE5380C);
                a = hh(a, b, c, d, buffer[i+ 1],  4, 0xA4BEEA44);
                d = hh(d, a, b, c, buffer[i+ 4], 11, 0x4BDECFA9);
                c = hh(c, d, a, b, buffer[i+ 7], 16, 0xF6BB4B60);
                b = hh(b, c, d, a, buffer[i+10], 23, 0xBEBFBC70);
                a = hh(a, b, c, d, buffer[i+13],  4, 0x289B7EC6);
                d = hh(d, a, b, c, buffer[i+ 0], 11, 0xEAA127FA);
                c = hh(c, d, a, b, buffer[i+ 3], 16, 0xD4EF3085);
                b = hh(b, c, d, a, buffer[i+ 6], 23, 0x04881D05);
                a = hh(a, b, c, d, buffer[i+ 9],  4, 0xD9D4D039);
                d = hh(d, a, b, c, buffer[i+12], 11, 0xE6DB99E5);
                c = hh(c, d, a, b, buffer[i+15], 16, 0x1FA27CF8);
                b = hh(b, c, d, a, buffer[i+ 2], 23, 0xC4AC5665);
				
                a = ii(a, b, c, d, buffer[i+ 0],  6, 0xF4292244);
                d = ii(d, a, b, c, buffer[i+ 7], 10, 0x432AFF97);
                c = ii(c, d, a, b, buffer[i+14], 15, 0xAB9423A7);
                b = ii(b, c, d, a, buffer[i+ 5], 21, 0xFC93A039);
                a = ii(a, b, c, d, buffer[i+12],  6, 0x655B59C3);
                d = ii(d, a, b, c, buffer[i+ 3], 10, 0x8F0CCC92);
                c = ii(c, d, a, b, buffer[i+10], 15, 0xFFEFF47D);
                b = ii(b, c, d, a, buffer[i+ 1], 21, 0x85845DD1);
                a = ii(a, b, c, d, buffer[i+ 8],  6, 0x6FA87E4F);
                d = ii(d, a, b, c, buffer[i+15], 10, 0xFE2CE6E0);
                c = ii(c, d, a, b, buffer[i+ 6], 15, 0xA3014314);
                b = ii(b, c, d, a, buffer[i+13], 21, 0x4E0811A1);
                a = ii(a, b, c, d, buffer[i+ 4],  6, 0xF7537E82);
                d = ii(d, a, b, c, buffer[i+11], 10, 0xBD3AF235);
                c = ii(c, d, a, b, buffer[i+ 2], 15, 0x2AD7D2BB);
                b = ii(b, c, d, a, buffer[i+ 9], 21, 0xEB86D391);
				
                a = add(a, temp_a);
                b = add(b, temp_b);
                c = add(c, temp_c);
                d = add(d, temp_d);
            }
			
            return hex( [a, b, c, d] );
        }
    });
};

bb.authmanager = (function($,gExport){
    var _pattern = 'UsernameToken Username="#username#", PasswordDigest="#digest#", Nonce="#nonce#", Created="#created#"';
    var _username;
    var _digest;
    var _nonce;
    var _created;
    var _secret;
    var _isauthenticated = false;
    var _maxtries = 3;
    var _numtries = 0;
    var _popupDialog;
    var _debug = false;
    
    var _init = function() {
        $(this).bind("bb-auth-required", _onAuthRequired );
        $(this).bind("bb-sudo-auth-required", _onSudoAuthRequired );
        $(this).bind("bb-auth-forbidden", _onAuthForbidden );
        $(this).bind("bb-auth-succeed", _onAuthSucceed );
        
        _log('initialization complete');
    }
	
    var _getCreated  = function() {
        return new Date();
    };
	
    var _getToken = function() {
        if (null != sessionStorage) {
            bbauth = sessionStorage.getItem('bb5-session-auth');
            if (null != bbauth && 64 <= bbauth.length) {
                _nonce = bbauth.substr(0, 32);
                _secret = bbauth.substr(32, 32);
                _username = bbauth.substr(64, bbauth.length-64);
            }
        }
		
        _updDigest();
		
        return _pattern.replace('#username#', ('undefined' == typeof(_username)) ? '' : _username)
                       .replace('#digest#', ('undefined' == typeof(_digest)) ? '' : _digest)
                       .replace('#nonce#', ('undefined' == typeof(_nonce)) ? '' : _nonce)
                       .replace('#created#', ('undefined' == typeof(_created)) ? '' : _created);
    };
	
    var _parseToken = function(token) {
        var result = {};
		
        var datas = token.substring(14, token.length).split(', ');
        for(i=0; i<datas.length; i++) {
            var expr = /(\w+)="(.+)"/;
            expr.exec(datas[i]);
            result[RegExp.$1.toLowerCase()] = RegExp.$2;
        }
		
        return result;
    };
	
    var _authenticate = function(username, password) {
        _numtries++;
        
        _setUsername(username);
        _setDigest(password);
        
        _log('Authentication');
        
        return _getToken();
    };
	
    var _logoff = function() {
        bb.webserviceManager.getInstance('ws_local_user').request('logoff', {
            success: function() {
                _log('Logoff');
                
                if (null != sessionStorage) 
                    sessionStorage.removeItem('bb5-session-auth');

                bb.wsCacheManager.getInstance().clean(1);
                document.location.reload();
//                //bb.wsCacheManager.getInstance().clean(2,"userSession");
//                bb.wsCacheManager.getInstance().clean(1);
//                setTimeout(function(){
//                    document.location.reload();
//                },700); //wait before reload
            }
        });
    };
    
    var _setDigest   = function(secret) {
        _secret = $.md5(secret);
        _created = _getCreated().toGMTString();
        _digest = $.md5(_nonce+_created+_secret);
		
        if (null != sessionStorage) {
            sessionStorage.setItem('bb5-session-auth', _nonce+_secret+_username);
        }
    };
	
    var _updDigest   = function() {
        _created = _getCreated().toGMTString();
        _digest = $.md5(_nonce+_created+_secret);
    }
	
    var _setNonce    = function(nonce) {
        _nonce = nonce;
    };
	
    var _setUsername = function(username) {
        _username = username;
    };
    
    var _onAuthSucceed = function() {
        _isauthenticated = true;
        _numtries = 0;
        _log('Authentication succeed');
    };
    
    var _onAuthRequired = function(event, token, request) {
        var self = this;
        var request = request;
        
        _log('Authentication need');
        
        if ('undefined' == typeof(token) )
            return $(self).trigger("bb-auth-forbidden");
        
        if (_maxtries <= _numtries)
            return $(self).trigger("bb-auth-forbidden");
        
        var infos = _parseToken(token);
        
        if ('undefined' != typeof(infos.nonce))
            _setNonce(infos.nonce);
        
        if (null == _popupDialog) {
            var popupDialog = bb.PopupManager.init({});
            _popupDialog = popupDialog.create('authenticationDialog',{
                dialogType : popupDialog.dialogType.ALERT,
                title: bb.i18n.__('authmanager.authentication_required'),
                buttons:{
                    popupBtnConnect: {
                        text: bb.i18n.__('authmanager.connect'),
                        click: function() {
                            var username = $('.bb5-dialog-authentication input[name="username"]').val();
                            var password = $('.bb5-dialog-authentication input[name="password"]').val();
                            
                            if ('' == username || '' == password) {
                                $(self).trigger('bb-auth-required', ['Nonce="'+self._nonce+'", ErrorCode="9002"', request]);
                            } else {
                                request.headers['X-BB-AUTH'] = self.authenticate( username, password);
                                $.ajax(request);
                                
				 if (0 < $('#bb5-toolbar-wrapper').length) $(this).dialog('close');
                            }
                        }
                    },
                    popupBtnAbort: {
                        text: bb.i18n.__('authmanager.abort'),
                        click: function() { 
                            $(this).dialog('close');
                            return $(self).trigger("bb-auth-forbidden");
                        }
                    }
                },
                i18n:{
                    username: bb.i18n.__('authmanager.username'),
                    password: bb.i18n.__('authmanager.password')
                }
            });
        }
        
        $(_popupDialog.dialog).find('.bb5-alert').empty();
        if ('undefined' != typeof(infos.errorcode) && 0 != infos.errorcode && 'undefined' != typeof(infos.errormessage) ) {
            if (0 < _numtries) {
                var remainingtries = _maxtries-_numtries;
                infos.errormessage = bb.i18n.__('authmanager.error.'+infos.errorcode);
                infos.errormessage += '<br/>'+bb.i18n.__('authmanager.'+((1 < remainingtries) ? 'tries_remaining' : 'try_remaining'), remainingtries);                
            }
            $(_popupDialog.dialog).find('.bb5-alert').html(infos.errormessage);            
        }
	
        _log('Opening dialog');
        $(_popupDialog.dialog).dialog('open');
        _popupDialog.show();
        
        return false;
    };
	
    var _onAuthForbidden = function (event) {
        var forbiddenPopup = bb.PopupManager.init({});
        _forbiddenPopup = forbiddenPopup.create('authenticationDialog',{
            dialogType : forbiddenPopup.dialogType.ALERT,
            title: bb.i18n.__('authmanager.forbiden_access'),
        });
        $(_forbiddenPopup.dialog).dialog('open');
         _forbiddenPopup.show();
        
        return false;
    };
	
    var _onSudoAuthRequired = function(event) {
        _log('Authentication failed');
        bb.end();
    };
    
    var _log = function(msg) {
        if (_debug) {
            console.log({
                message:         msg,
                isauthenticated: _isauthenticated,
                numtries:        _numtries,
                dialog: _popupDialog ? $(_popupDialog.dialog).dialog('isOpen') : null
            });
        }
    }
    
    return {
        init:        _init,
        authenticate:_authenticate,
        logoff:      _logoff,
        getToken:    _getToken,
        setUsername: _setUsername,
        setDigest:   _setDigest,
        setNonce:    _setNonce
    };
})(jQuery,window);