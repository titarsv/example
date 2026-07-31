var translitData = {
 "Ё": "yo",
 "Й": "i",
 "Ц": "ts",
 "У": "u",
 "К": "k",
 "Е": "e",
 "Н": "n",
 "Г": "g",
 "Ш": "sh",
 "Щ": "sch",
 "З": "z",
 "Х": "h",
 "Ъ": "",
 "ё": "yo",
 "й": "i",
 "ц": "ts",
 "у": "u",
 "к": "k",
 "е": "e",
 "н": "n",
 "г": "g",
 "ш": "sh",
 "щ": "sch",
 "з": "z",
 "х": "h",
 "ъ": "'",
 "Ф": "f",
 "Ы": "y",
 "В": "v",
 "А": "a",
 "П": "p",
 "Р": "r",
 "О": "o",
 "Л": "l",
 "Д": "d",
 "Ж": "zh",
 "Э": "e",
 "ф": "f",
 "ы": "y",
 "в": "v",
 "а": "a",
 "п": "p",
 "р": "r",
 "о": "o",
 "л": "l",
 "д": "d",
 "ж": "zh",
 "э": "e",
 "Я": "ya",
 "Ч": "ch",
 "С": "s",
 "М": "m",
 "И": "i",
 "Т": "t",
 "Ь": "",
 "Б": "b",
 "Ю": "yu",
 "я": "ya",
 "ч": "ch",
 "с": "s",
 "м": "m",
 "и": "i",
 "т": "t",
 "ь": "",
 "б": "b",
 "ю": "yu",
 " ": "-",
 ",": "",
 ".": "",
 "'": "",
 "\\": "",
 "\"": "",
 "/": "",
};

String.prototype.transliterate = function() {
 return this.split('').map(function (char) {
   return (translitData[char]!==undefined) ? translitData[char].toLowerCase() : char.toLowerCase();
 }).join("");
};

$(document).ready(function() {

 //Транслит на страницах товара и категории

   if ($('[data-translit]').length > 0) {
     var input = $('[data-translit="input"]');
     var output = $('[data-translit="output"]');
     input.on('keyup', function(){
       output.val($(this).val().transliterate());
     });
   }
 });