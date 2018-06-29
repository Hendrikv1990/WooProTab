jQuery(document).ready(function($){ 
    $('.ftabcustom .add-box').click(function(){
        
        var box_html = $('<div class="tabsform"><div class="tab_title_label">Tab Title:</div><div class="tab_icon"><select class="select se2" name="icon_meta_box_select" id="icon_meta_box_select" required><option value="">Select Tab Icon</option><option value="#xf069;" >&#xf069; Astarik</option><option value="#xf1fe;" >&#xf1fe; Chart</option><option value="#xf0f3;">&#xf0f3; Bell</option><option value="#xf02d;">&#xf02d; Book</option><option value="#xf02e;" >&#xf02e; Bookmark</option><option value="#xf274;" >&#xf274; Calander</option><option value="#xf030;" >&#xf030; Camera</option><option value="#xf217;" >&#xf217; Cart</option><option value="#xf14a;" >&#xf14a; Check</option><option value="#xf013;" >&#xf013; Cog</option><option value="#xf086;" >&#xf086; Comments</option><option value="#xf019;" >&#xf019; Download</option><option value="#xf0e0;" >&#xf0e0; Envelope</option><option value="#xf06a;" >&#xf06a; Exclamation Circle</option><option value="#xf071;" >&#xf071; Exclamation Triangle</option><option value="#xf06e;" >&#xf06e; Eye</option><option value="#xf1ac;" >&#xf1ac; Fax</option><option value="#xf008;" >&#xf008; Film / Video</option><option value="#xf024;" >&#xf024; Flag</option><option value="#xf004;" >&#xf004; Heart</option><option value="#xf015;" >&#xf015; Home</option><option value="#xf254;" >&#xf254; Hourglass</option><option value="#xf03e;">&#xf03e; Image</option><option value="#xf03c;" >&#xf03c; Indent</option><option value="#xf05a;" >&#xf05a; Info</option><option value="#xf084;" >&#xf084; Key</option><option value="#xf0e3;" >&#xf0e3; Legal</option><option value="#xf1cd;" >&#xf1cd; Life Saver</option><option value="#xf0eb;" >&#xf0eb; Light Bulb</option><option value="#xf03a;">&#xf03a; List</option><option value="#xf041;">&#xf041; Map Marker</option><option value="#xf091;" >&#xf091; Trophy</option><option value="#xf0d1;" >&#xf0d1; Truck</option><option value="#xf02b;" >&#xf02b; Tag</option><option value="#xf03d;" >&#xf03d; Video Camera</option><option value="#xf0ad;" >&#xf0ad; Wrench</option><option value="#xf166;" >&#xf166; Youtube</option></select></div><div class="add_tab_bt"><span class="preview button remove-box"><span class="fontas_bt">&#xf056;</span> Remove</span></div></div>');
        box_html.hide();
        $('.tab_form .tabsform:last').after(box_html);
        box_html.fadeIn('slow');
        return false;
    });
    $('.tab_form').on('click', '.remove-box', function(){
        var n = $('.tabsform').length + 1;
        $(this).parent().css( 'background-color', '#FF6C6C' );
        $(this).parent().fadeOut("slow", function() { 
            $(this).parent().remove(); 
        });
        return false;
    });
});


