/**
* as_jsfunclib.js: common javascript function set, (small version)
* Author / collector Alexander Selifonov <alex [at] selifan.ru>
* @version 1.043.178
* updated  2016-01-27
* License MIT
**/
var _responsecontext = null;

function SetFormValue(felem,newval,fireonchange) {
  var itmp;
  var fobj = 0;
  if(typeof(felem)=="string") {
      try { fobj=eval(felem); } catch(e) {};
      if(typeof(fobj)!="object" && felem.substring(0,1)=='#') try{
          fobj = $(felem).get(0);
      } catch(e) {};

      if(typeof(fobj)!="object") try {
          fobj = $('*[name='+felem+']').get(0);
      } catch(e) {};
  }
  else fobj = felem;
  try { var eltype = fobj.type; } catch(e) {
      if(as_jsdebug) alert('SetFormValue: No form element for '+felem+'='+newval);
      return false;
  }
  switch(eltype) {
  case 'select-one':
    for(var itmp=0;itmp<fobj.options.length;itmp++) {
        if(fobj.options[itmp].value==newval) {
            fobj.selectedIndex=itmp;
            break;
        }
    }
    break;
  case 'checkbox':
    fobj.checked = (newval>0);
    break;
  default:
    fobj.value = (fobj.type=='text' && !newval)? "" : newval;
    break;
  }
  if(!!fireonchange) {
    if(fobj.onchange) { fobj.onchange(); }
    if(fobj.onclick) fobj.onclick();
  }
}

function asGetObj(name){
 if (document.getElementById) {
   return document.getElementById(name);
 }
 else if (document.all) {
   return document.all[name];
 }
 else if (document.layers)
 {
   if (document.layers[name]) {
     return document.layers[name];
   }
   else { return document.layers.testP.layers[name]; }
 }
 return null;
}

function round_2dec(num) { // rounds to 2 decimal digits
  var rrr = 0.01*Math.round(0.01*Math.round(num*10000)) + '';
  if (rrr.indexOf(".") != -1) rrr = rrr.substring(0,rrr.indexOf(".")+4);
  rrr -= 0;
  return rrr;
}

// common AJAX call with handling "structured" response
// TODO: manage AJAX calls queue
function SendServerRequest(url,params,modalmode,fireevents,rq_finalaction) {
    if(typeof(modalmode)=='undefined') { // create layer above html document
      modalmode=false;
    }
    if(typeof(fireevents)=='undefined') fireevents=false;

    $.post(url,params, function(data) {
       handleResponseData(data,fireevents);
       if(as_cumulmsg!='' && $.isFunction(ShowAccumulatedText))
           ShowAccumulatedText(as_cumulmsg);
       if(rq_finalaction) {
           if($.isFunction(rq_finalaction)) rq_finalaction();
           else eval(rq_finalaction);
       }
    });
    return false;
}

function SetResponseContext(cntxt) {
    _responsecontext = cntxt;
}

function setFormValue(fname,fval,fireevt) {
    alert('setFormValue '+fname+':'+fval);
    var felem=$("input[name="+vals1+"]",_responsecontext);
    if(!felem.get(0))  { felem=$("select[name="+vals1+"]",_responsecontext); }
    if(!felem.get(0))  { felem=$("textarea[name="+vals1+"]",_responsecontext); }
    if(!felem.get(0))  { felem=$("#"+vals1,_responsecontext); }
    if(!felem.get(0)) { alert(vals1+': field not found'); return; }
    var inob = felem.get(0);
    try {
        eltype = inob.type;
        if(eltype=='checkbox') { // non-zero value means 'checked' !
              inob.checked = ((vals2-0)!=0);
            $(inob).attr('checked', ((vals2-0)!=0));
//                        if(vals1=='bea_prg3') alert(vals1+': handleResponseData ckeckbox to '+vals2);
        }
        else if(eltype=='radio') {
            $(felem).each(function() {
//                            alert($(this).val() +' ==(radio) '+ vals2);
                if($(this).val() == vals2) {
//                                alert(vals1+': found for radio trigger:'+vals2);
                    $(this).attr('checked',true);
                    if(fireevt) $(this).trigger('click');
                }
            });

        }
        else {
            $(inob,_responsecontext).val(vals2);
        }
    } catch (e){};
    if(fireevt){
        if(inob) {
            if(eltype!=='radio') {
                if($.isFunction(inob.onchange)) { try { inob.onchange(); } catch (e){} }
                if($.isFunction(inob.onclick)) try { inob.onclick(); }  catch (e){};
            }
        }
    }

}
// parse AJAX response and execute passed cmds(alerts,change DOM attributes/form values/...)
// if array UDFsetValues[] contains varname, call UDFsetValuesFunc() with this data pair instead of std setting
function handleResponseData(data,fireonchange) {
//    alert("processing response: "+data); //debug
    as_cumulmsg = '';
    var splt = data.split("\t"),fselector, felem;
    if(splt[0] =="1") {
        for(var kk=1;kk<splt.length;kk++) {
            var vals = splt[kk].split(/[|\f]/);
            var vals1 = (vals.length>1)? vals[1]: '';
            var vals2 = (typeof(vals[2])=='undefined') ? '': vals[2];

            var cmd = vals[0].trim();
            switch(cmd) { //<3>
            case 'addmsg': as_cumulmsg += vals[1]; break;
            case 'set':
//                var tmpobj = $("#"+vals[1]).get(0);  SetFormValue(tmpobj,vals[2],true);
                if(typeof(UDFsetValues)==='object' && IsInArray(UDFsetValues,vals1)) {
                    UDFsetValuesFunc(vals1,vals2);
                    continue;
                }

                felem=$("input[name="+vals1+"]",_responsecontext);
                if(!felem.get(0))  { felem=$("select[name="+vals1+"]",_responsecontext); }
                if(!felem.get(0))  { felem=$("textarea[name="+vals1+"]",_responsecontext); }
                if(!felem.get(0))  { felem=$("#"+vals1,_responsecontext); }
                if(!felem.get(0)) { /*alert(vals1+': field not found');*/ continue; }
                var inob = felem.get(0);
                try {
                    eltype = inob.type;
                    if(eltype=='checkbox') { // non-zero value means 'checked' !
                          inob.checked = ((vals2-0)!=0);
                        $(inob).attr('checked', ((vals2-0)!=0));
                    }
                    else if(eltype=='radio') {
                        $(felem).each(function() {
                            if($(this).val() == vals2) {
                                $(this).attr('checked',true);
                                if(fireonchange) $(this).trigger('click');
                            }
                        });

                    }
                    else {
                        $(inob,_responsecontext).val(vals2);
                    }
                } catch (e){};
                if(fireonchange){
                    if(inob) {
//                        $(felem).trigger('click').trigger('change');
                        if(eltype!=='radio') {
//                            if(vals1=='bea_prg3') alert('bea_prg3 is '+inob.checked);
                            if($.isFunction(inob.onchange)) { try { inob.onchange(inob); } catch (e){} }
//                            if(vals1=='bea_prg3') alert('after CHANGE - bea_prg3 is '+inob.checked);
                            if($.isFunction(inob.onclick)) try { inob.onclick(inob); }  catch (e){};
//                          if($.isFunction(felem[0].onclick)) try { felem.onclick(); }  catch (e){};
//                            if(vals1=='bea_prg3') alert('after CLICK - bea_prg3 is '+inob.checked);
                        }
                    }
                }
                break;
            case 'html':   $("#"+vals1).html(vals2); break;
            case 'title':  $("#"+vals1).attr("title",vals2); break;
            case 'enable':
                if(vals2!='0') $(vals1).removeAttr('disabled');
                else { $(vals1).attr('disabled',true); }
                if(vals2=="1") $(vals1).removeAttr("readonly");
                break;
            case 'readonly':
                $(vals1).attr("readonly",(vals2!='0'));
                break;

            case 'show':
                if(vals2==='' || !!vals2) { //show/slideDown/
                    $(vals1).show();
                } else { //hide/slideUp
                    $(vals1).hide();
                }
                break;
            case 'hide':
               $(vals1).hide();
               break;
            case "css": // change css : tag:value;tag2:value;...
                var tcss = {};
                var cssplt = vals2.split(";");
                for(var cssid in cssplt) {
                    var splt2 = cssplt[cssid].split(":");
                    if(splt2[1]) tcss[splt2[0]]=splt2[1];
                }
                $(vals1).css(tcss);
                break;
            case 'attr': case 'prop': // set DOM attribute
//              alert(vals);
              $(vals1).prop(vals2,vals[3]);
              break;
            case 'alert' : alert(vals1); break;
            case 'alertdlg' : case 'showmessage': // showmessage, text [, title, err_class]
              showMessage(vals2,vals1,vals[3]);
              break;
            case 'talert':
                var vtime = (typeof(vals[2])==='undefined') ? 3 : parseInt(vals[2]);
                var sclass = (typeof(vals[3])==='undefined') ? false : vals[3];
                TimeAlert(vals1,vtime, sclass);
                break;
            case 'confirm': // confirm : dlg-title : dlg-text : funcYes [: funcNo]
               var dlgParam = { title: vals1, text: vals2 };
               var fYes = vals[3] ? vals[3]: false;
               var fNo = vals[4] ? vals[4]: false;
               dlgConfirm(dlgParam,fYes, fNo);
               break;
            case 'seladd': case 'addoption' :// add select box option: selectadd | select_id | value [text]
              var selobj = $("#"+vals1).get(0);
              if(selobj.type=="select-one"){
                  var opval = vals2;
                  var optext = (typeof(vals[3])=="string")?vals[3]:opval;
                  $(selobj).append('<option value="'+opval+'">'+optext+"</option>");
              }
              break;
            case 'selclear': // clear <select> box from all options
              var selobj = $("#"+vals1).get(0);
              if(selobj.type=="select-one"){ selobj.options.length=0; }
              break;
            case 'gotourl': window.location.href = vals1; break;
            case 'reloadpage': window.location.reload(true); break;
            case 'flash'  : FlashDiv(vals1); break;
            case 'eval'   :
                try{eval(vals1)}catch(e){
                    if(__JsModeDebug) alert('eval internal error for \n'+vals1);
                };
                break;
            case 'viewlog':
                $("body").floatWindow({
                   html:'<div class="div_outline" id="div_viewlog" style="width:800px;height:490px;"><div style="overflow:auto;height:450px; padding:0.2em; margin:0.2em">'+vals1+'</div></div>'
                   , id: 'div_viewlog'
                   ,left:100, top:50
                   ,title: (vals2 ? vals2 : 'Operation log')
/*                   ,init : function() {
                       $("#div_viewlog").html(vals1);
                   }
*/
                });
                break;
            case 'remove' : $(vals1).remove(); break;// removes code from document
            case 'trigger': // jqGrid or other "trigger" supported operation
                var gridOper = vals2 ? vals2 : 'reloadGrid';
                $(vals1).trigger(gridOper);
                break;
            default:
                alert("handleResponseData: unsupported cmd ["+vals+']');
                break;
            }
        }
    }
    else TimeAlert(data,4,"msg_error");
}

// shows ui dialog window with desired title, text and OK button
function showMessage(stitle,stext, bk_class) {
    var dlgOpts = {width:650,resizable:false, zIndex: 500
      ,buttons: [{text: "OK",click: function() {$( this ).dialog( "close" ).remove();}}]
      ,open: function(event,ui) {
        $('.ui-dialog').css('z-index',9002);
        $('.ui-widget-overlay').css('z-index',9001);
       }
    };

    if(!!stitle) dlgOpts.title = stitle;
    dlgOpts.dialogClass = (typeof(bk_class)==='string') ? bk_class : asJ.defaultDlgClass;
    $('<div id="dlg_showmessage" style="z-index:9900">'+stext+'</div>').dialog(dlgOpts);
//    $('#dlg_showmessage').css('z-index','20000');
}

function parseIntList($strg,$nonegative) {
    var ret = [];
    var rtmp = $strg.split(/[,;]/);
    for(var nn in rtmp) {
        if(rtmp[nn]==='') continue;
        $spt = rtmp[nn].split('-');
        ret.push(parseInt($spt[0]));
        if(!!($spt[1]) && parseInt($spt[1])>parseInt($spt[0])) for(var i=parseInt($spt[0])+1;i<=parseInt($spt[1]);i++) { ret.push(i); }
    }
    return ret;
}
StrUtils = {
    padl: function(strg,schar,len) {
        var ret = strg+'';
        while(ret.length < len) { ret = schar+ret; }
        return ret;
    }
   ,padr: function(strg,schar,len) {
        var ret = strg+'';
        while(ret.length < len) { ret += schar; }
        return ret;
    }
}

function getSelectedText(objectId) {
    var textComponent = document.getElementById(objectId);
    var selectedText;
    if (document.selection != undefined) { // IE version
        textComponent.focus();
        var sel = document.selection.createRange();
        selectedText = sel.text;
    }

    else if (textComponent.selectionStart != undefined) { // Mozilla version
        var startPos = textComponent.selectionStart;
        var endPos = textComponent.selectionEnd;
        selectedText = textComponent.value.substring(startPos, endPos);
    }
    return selectedText;
}

// msie 6, or compatibility mode - no indexOf on arrays, so add it
if(!Array.indexOf){
  Array.prototype.indexOf = function(obj){
    for(var i=0; i<this.length; i++){
      if(this[i]==obj){
        return i;
      }
    }
    return -1;
  }
}
if (!String.prototype.trim) {
   String.prototype.trim=function(){return this.replace(/^\s+|\s+$/g, '');};
}