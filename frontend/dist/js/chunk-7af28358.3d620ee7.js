(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["chunk-7af28358"],{"13f1":function(t,e,n){},2489:function(t,e,n){"use strict";n.r(e);var c=n("7a23"),u=Object(c["h"])("span",null,"Logging out...",-1),s=Object(c["h"])("div",{class:"spinner-border text-primary"},null,-1);function a(t,e,n,a,r,o){return Object(c["q"])(),Object(c["e"])(c["a"],null,[u,s],64)}var r=n("d4ec"),o=n("bee2"),i=n("262e"),f=n("2caf"),b=n("091e"),h=n("ecbc"),p=n("ce1f"),d=function(t){Object(i["a"])(n,t);var e=Object(f["a"])(n);function n(){return Object(r["a"])(this,n),e.apply(this,arguments)}return Object(o["a"])(n,[{key:"mounted",value:function(){var t=this;b["a"].auth.logout().then((function(e){!e.success&&t.$notifications.error("Ошибка выхода из аккаунта!"),h["d"].context(t.$store).dispatch(h["b"],!e.success),t.$router.push({path:e.success?"/auth/login":"/"})}))}}]),n}(p["b"]);n("f43d");d.render=a;e["default"]=d},f43d:function(t,e,n){"use strict";n("13f1")}}]);
//# sourceMappingURL=chunk-7af28358.3d620ee7.js.map