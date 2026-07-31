<script type="text/javascript">
    tinyMCEPreInit = {
        baseURL: "/js/larchik/tinymce",
        suffix: ".min",
        dragDropUpload: true,
        mceInit: {
            @foreach($editors as $i => $editor_id)
                {{ $i > 0 ? ',' : '' }}
                '{{ $editor_id }}':{
                    theme:"modern",
                    skin:"lightgray",
                    language:"ru",
                    formats:{
                        alignleft: [
                            {selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li", styles: {textAlign:"left"}},
                            {selector: "img,table,dl.wp-caption", classes: "alignleft"}
                        ],
                        aligncenter: [
                            {selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li", styles: {textAlign:"center"}},
                            {selector: "img,table,dl.wp-caption", classes: "aligncenter"}
                        ],
                        alignright: [
                            {selector: "p,h1,h2,h3,h4,h5,h6,td,th,div,ul,ol,li", styles: {textAlign:"right"}},
                            {selector: "img,table,dl.wp-caption", classes: "alignright"}
                        ],
                        strikethrough: {inline: "del"}
                    },
                    relative_urls:false,
                    remove_script_host:false,
                    convert_urls:false,
                    browser_spellcheck:true,
                    fix_list_elements:true,
                    entities:"38,amp,60,lt,62,gt",
                    entity_encoding:"raw",
                    keep_styles:false,
                    cache_suffix:"wp-mce-4800-{{ date('Ymd') }}",
                    resize:"vertical",
                    menubar:false,
                    branding:false,
                    preview_styles:"font-family font-size font-weight font-style text-decoration text-transform",
                    end_container_on_empty_block:true,
                    wpeditimage_html5_captions:true,
                    wp_lang_attr:"ru-RU",
                    wp_keep_scroll_position:false,
                    wp_shortcut_labels:{
                        "Heading 1":"access1",
                        "Heading 2":"access2",
                        "Heading 3":"access3",
                        "Heading 4":"access4",
                        "Heading 5":"access5",
                        "Heading 6":"access6",
                        "Paragraph":"access7",
                        "Blockquote":"accessQ",
                        "Underline":"metaU",
                        "Strikethrough":"accessD",
                        "Bold":"metaB",
                        "Italic":"metaI",
                        "Code":"accessX",
                        "Align center":"accessC",
                        "Align right":"accessR",
                        "Align left":"accessL",
                        "Justify":"accessJ",
                        "Cut":"metaX",
                        "Copy":"metaC",
                        "Paste":"metaV",
                        "Select all":"metaA",
                        "Undo":"metaZ",
                        "Redo":"metaY",
                        "Bullet list":"accessU",
                        "Numbered list":"accessO",
                        "Insert\/edit image":"accessM",
                        "Remove link":"accessS",
                        "Toolbar Toggle":"accessZ",
                        "Insert Read More tag":"accessT",
                        "Insert Page Break tag":"accessP",
                        "Distraction-free writing mode":"accessW",
                        "Keyboard Shortcuts":"accessH"
                    },
                    content_css:"/css/larchik/dashicons.min.css,/js/larchik/tinymce/skins/wordpress/wp-content.css",
                    plugins:"charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpautoresize,wpeditimage,wplink,wpdialogs,wptextpattern,wpview",
                    // plugins:"charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpautoresize,wpeditimage,wpemoji,wpgallery,wplink,wpdialogs,wptextpattern,wpview",
                    external_plugins:{},
                    selector:"#{{ $editor_id }}",
                    wpautop:true,
                    indent:false,
                    toolbar1:"formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,wp_more,spellchecker,fullscreen,wp_adv,separator,wpUserAvatar",
                    toolbar2:"strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help",
                    toolbar3:"",
                    toolbar4:"",
                    tabfocus_elements:":prev,:next",
                    extended_valid_elements: 'span[*],p[*]',
                    body_class:"{{ $editor_id }} post-type-page post-status-publish page-template-page-main locale-ru-ru"
                }
            @endforeach
        },
        qtInit: {
            @foreach($editors as $i => $editor_id)
                {{ $i > 0 ? ',' : '' }}
                '{{ $editor_id }}':{
                    id:"{{ $editor_id }}",
                    buttons:"strong,em,link,block,del,ins,img,ul,ol,li,code,more,close"
                }
            @endforeach
        },
        ref: {
            plugins:"charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpautoresize,wpeditimage,wpemoji,wpgallery,wplink,wpdialogs,wptextpattern,wpview",
            theme:"modern",
            language:"ru"
        },
        load_ext: function(url,lang){
            var sl=tinymce.ScriptLoader;
            sl.markDone(url+'/langs/'+lang+'.js');
            sl.markDone(url+'/langs/'+lang+'_dlg.js');
        }
    };
</script>