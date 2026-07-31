const googleTranslateConfig = {
    lang: "ru"
};

window.TranslateInit = function() {
    let code = TranslateGetCode();
    console.log(code);
    // Находим флаг с выбранным языком для перевода и добавляем к нему активный класс
    jQuery('[data-google-lang="' + code + '"]').addClass('language__img_active');

    if (code == googleTranslateConfig.lang) {
        // Если язык по умолчанию, совпадает с языком на который переводим
        // То очищаем куки
        TranslateClearCookie();
    }

    // Инициализируем виджет с языком по умолчанию
    new google.translate.TranslateElement({
        pageLanguage: googleTranslateConfig.lang,
    });

    // Вешаем событие клик на флаги
    jQuery('[data-google-lang]').click(function (e) {
        e.preventDefault();
        TranslateSetCookie(jQuery(this).attr("data-google-lang"));
        // Перезагружаем страницу
        window.location.reload();
    });
};

function TranslateGetCode() {
    // Если куки нет, то передаем дефолтный язык
    let lang = (getCookie('googtrans') != undefined && getCookie('googtrans') != "null") ? getCookie('googtrans') : googleTranslateConfig.lang;
    return lang.substr(-2);
}

function TranslateClearCookie() {
    setCookie('googtrans', null, {path: '/'});
    setCookie("googtrans", null, {
        path: '/',
        domain: "." + document.domain
    });
}

function TranslateSetCookie(code) {
    // Записываем куки /язык_который_переводим/язык_на_который_переводим
    setCookie('googtrans', "/ru/" + code, {path: '/'});
    setCookie("googtrans", "/ru/" + code, {
        path: '/',
        domain: "." + document.domain
    });
}

function setCookie(name, value, options) {
    if (options.expires instanceof Date) {
        options.expires = options.expires.toUTCString();
    }

    let updatedCookie = encodeURIComponent(name) + "=" + value;

    for (let optionKey in options) {
        updatedCookie += "; " + optionKey;
        let optionValue = options[optionKey];
        if (optionValue !== true) {
            updatedCookie += "=" + optionValue;
        }
    }

    document.cookie = updatedCookie;
}

function getCookie(name) {
    let matches = document.cookie.match(new RegExp(
        "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
    ));
    return matches ? matches[1] : undefined;
}

function loadScript(src,_timeout) {

    return new Promise(function(resolve, reject){
        if(!src){
            reject(new TypeError("filename is missing"));
            return;
        }

        var script=document.createElement("script"),
            timer,
            head=document.getElementsByTagName("head")[0];


        head.appendChild(script);

        function leanup(){
            clearTimeout(timer);
            timer=null;
            script.onerror=script.onreadystatechange=script.onload=null;
        }

        function onload(){
            leanup();
            if(!script.onreadystatechange||(script.readyState&&script.readyState=="complete")){
                resolve(script);
            }
        }

        script.onerror=function(error){
            leanup();
            head.removeChild(script);
            script=null;
            reject(new Error("network"));
        };

        if (script.onreadystatechange === undefined) {
            script.onload = onload;
        } else {
            script.onreadystatechange = onload;
        }

        timer=setTimeout(script.onerror,_timeout||30000);
        script.setAttribute("type", "text/javascript");
        script.setAttribute("src", src);
    });
}

var css = '.skiptranslate { display: none; }',
    head = document.head || document.getElementsByTagName('head')[0],
    style = document.createElement('style');

head.appendChild(style);

style.type = 'text/css';
if (style.styleSheet){
    // This is required for IE8 and below.
    style.styleSheet.cssText = css;
} else {
    style.appendChild(document.createTextNode(css));
}

if(typeof jQuery !== 'function'){
    loadScript("//code.jquery.com/jquery-3.7.1.min.js").then(function(){
        loadScript("//translate.google.com/translate_a/element.js?cb=TranslateInit").then(function(){},function(error){});
    },function(error){});
}else{
    loadScript("//translate.google.com/translate_a/element.js?cb=TranslateInit").then(function(){},function(error){});
}