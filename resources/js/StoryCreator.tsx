import axios from "axios";
import { useEffect, useRef, useState } from "react";
import CloseButton from "./CloseButton";
import type { Master, Service } from "./types";

type Style = "minimal" | "dark" | "soft" | "accent" | "premium";
type TemplateSetting = { photo_url?: string; image_url: string | null; font: "system" | "serif" | "rounded" | "elegant" | "classic" | "bold" | "mono"; scale: number; text_color: string; image_position?: "top" | "center" | "bottom"; image_horizontal?: "left" | "center" | "right"; image_zoom?: number; shade?: number };
type ExtraTheme = "lashes"|"makeup"|"massage"|"skin"|"barber"|"smooth";
type StoryCatalog = Partial<Record<"nails"|"brows"|"hair"|ExtraTheme, Record<string,TemplateSetting>>>;
const fontFace=(font:TemplateSetting["font"]|undefined)=>({
  system: 'system-ui, sans-serif',
  serif: 'Georgia, "Times New Roman", serif',
  rounded: '"Trebuchet MS", Arial, sans-serif',
  elegant: '"Palatino Linotype", "Book Antiqua", Georgia, serif',
  classic: 'Baskerville, "Times New Roman", serif',
  bold: '"Arial Black", Arial, sans-serif',
  mono: '"Courier New", monospace',
}[font??"system"] ?? 'system-ui, sans-serif');
const photoSource=(catalog:StoryCatalog,theme:"nails"|"hair"|"brows"|ExtraTheme,id:string,fallback:string)=>catalog[theme]?.[id]?.image_url||fallback;
type CategoryTemplate = TemplateSetting & { id: string; name: string; description: string; image_url: string | null; };
type CategoryGroup = { id: string; name: string; activities: Record<string,string> };
type CategoryResponse = { groups?: CategoryGroup[]; category: { industry: string; activity: string; name: string } | null; templates: CategoryTemplate[] };
const categoryLook: Record<string,{bg:string; end:string; fg:string; action:string; actionText:string}>={
 editorial:{bg:"#F4F0E9",end:"#DCD1C5",fg:"#171717",action:"#171717",actionText:"#FFFFFF"},
 proof:{bg:"#27333A",end:"#6B7779",fg:"#FFFFFF",action:"#FFFFFF",actionText:"#27333A"},
 premium:{bg:"#171514",end:"#524236",fg:"#FFF5E7",action:"#E3C58F",actionText:"#171514"},
 warm:{bg:"#EDD4BD",end:"#C98979",fg:"#171717",action:"#171717",actionText:"#FFFFFF"},
 urban:{bg:"#1D1D27",end:"#805B6F",fg:"#FFFFFF",action:"#FFFFFF",actionText:"#202027"},
 soft:{bg:"#FAEDF0",end:"#DCD3D2",fg:"#303038",action:"#303038",actionText:"#FFFFFF"},
 promo:{bg:"#9D1A36",end:"#DF575C",fg:"#FFFFFF",action:"#FFFFFF",actionText:"#9D1A36"},
 seasonal:{bg:"#526D5B",end:"#B8A07A",fg:"#FFFFFF",action:"#FFFFFF",actionText:"#314539"},
};

const palettes: Record<Style, { name: string; bg: string; fg: string; sub: string; action: string; actionText: string }> = {
    minimal: { name: "Минимализм", bg: "#F5F4F1", fg: "#202020", sub: "#76716D", action: "#202020", actionText: "#FFFFFF" },
    dark: { name: "Графит", bg: "#1B1D22", fg: "#FFFFFF", sub: "#B8BAC0", action: "#FFFFFF", actionText: "#1B1D22" },
    soft: { name: "Нежный", bg: "#F8E3E8", fg: "#6C304B", sub: "#9E6B80", action: "#6C304B", actionText: "#FFFFFF" },
    accent: { name: "Акцент", bg: "#CE303E", fg: "#FFFFFF", sub: "#FBE5E7", action: "#FFFFFF", actionText: "#AE2632" },
    premium: { name: "Премиум", bg: "#26342D", fg: "#F3EAD7", sub: "#C9BBA3", action: "#DDC390", actionText: "#26342D" },
};

type Theme = "nails"|"hair"|"barber"|"brows"|"lashes"|"makeup"|"massage"|"skin"|"smooth"|"general"|"category";
const themes: Record<Theme,string> = {category:"Моя категория",nails:"Маникюр",hair:"Волосы",barber:"Барбер",brows:"Брови",lashes:"Ресницы",makeup:"Макияж",massage:"Массаж",skin:"Косметология",smooth:"Эпиляция",general:"Универсальный"};
const rules: [Theme,string[]][]=[
 ["nails",["маникюр","педикюр","ногт","nail","шеллак","гель лак"]],
 ["brows",["бров","brow","окрашивание бровей","коррекция бровей"]],
 ["lashes",["ресниц","lash"]],
 ["makeup",["визаж","макияж","makeup","мейкап"]],
 ["massage",["массаж","спа","spa","релакс"]],
 ["smooth",["эпиляц","депиляц","шугаринг","waxing"]],
 ["skin",["косметолог","пилинг","чистка лица"]],
 ["barber",["барбер","бород","фейд","fade","мужская стрижка","брить"]],
 ["hair",["стриж","окрашив","волос","причес","причёс","парикмах","hair"]]
];
const norm = (v:string) => v.toLowerCase().replace(/ё/g,"е");
export function storyTheme(service:Service,master:Master):Theme {
 for(const value of [service.name,service.description||"",[master.specialty,master.activity,master.industry].filter(Boolean).join(" ")]) {
  for(const [theme,words] of rules) if(words.some(word=>norm(value).includes(norm(word)))) return theme;
 }
 return "general";
}
function drawArt(ctx:CanvasRenderingContext2D,theme:Theme){
 ctx.save();ctx.translate(725,710);ctx.globalAlpha=.36;
 const c:Record<Theme,string>={category:"#754E5D",nails:"#C73855",hair:"#65413D",barber:"#353C44",brows:"#6D453D",makeup:"#B84963",massage:"#A27868",skin:"#BB8B7A",lashes:"#905B71",smooth:"#A4817E",general:"#A5636A"};
 ctx.fillStyle=c[theme];ctx.strokeStyle=c[theme];ctx.lineCap="round";ctx.lineJoin="round";
 if(theme==="nails"){
  ctx.rotate(-.22);ctx.beginPath();ctx.moveTo(-150,180);ctx.lineTo(-125,-95);ctx.quadraticCurveTo(-118,-156,-87,-143);ctx.quadraticCurveTo(-63,-138,-70,-102);ctx.lineTo(-72,27);ctx.lineTo(-38,-205);ctx.quadraticCurveTo(-28,-253,5,-235);ctx.quadraticCurveTo(24,-226,17,-190);ctx.lineTo(-7,29);ctx.lineTo(46,-219);ctx.quadraticCurveTo(60,-267,91,-244);ctx.quadraticCurveTo(110,-230,99,-195);ctx.lineTo(55,34);ctx.lineTo(113,-149);ctx.quadraticCurveTo(128,-186,156,-163);ctx.quadraticCurveTo(171,-147,157,-117);ctx.lineTo(87,100);ctx.quadraticCurveTo(186,29,214,77);ctx.quadraticCurveTo(232,109,196,140);ctx.lineTo(106,236);ctx.quadraticCurveTo(-45,292,-150,180);ctx.fill();
  ctx.globalAlpha=.95;ctx.fillStyle="#F8D5D9";for(const [x,y] of [[-99,-122],[-8,-214],[69,-224],[132,-148]]){ctx.beginPath();ctx.roundRect(x-18,y-8,37,65,18);ctx.fill();}
 } else if(theme==="hair"||theme==="barber"){
  ctx.beginPath();ctx.arc(0,12,194,Math.PI,0);ctx.lineTo(165,208);ctx.lineTo(-161,208);ctx.closePath();ctx.fill();ctx.globalAlpha=.7;ctx.fillStyle="#D5AE98";ctx.beginPath();ctx.ellipse(0,10,121,169,0,0,Math.PI*2);ctx.fill();
  if(theme==="barber"){ctx.fillStyle="#363A41";ctx.fillRect(90,-230,78,238);for(let x=98;x<166;x+=13)ctx.fillRect(x,-260,6,32);}
 }else if(theme==="brows"||theme==="makeup"||theme==="skin"){
  ctx.beginPath();ctx.ellipse(0,0,207,245,0,0,Math.PI*2);ctx.fill();
  ctx.globalAlpha=.7;ctx.fillStyle="#F2CFBE";ctx.beginPath();ctx.ellipse(0,0,161,208,0,0,Math.PI*2);ctx.fill();
  ctx.strokeStyle="#573E3C";ctx.lineWidth=22;ctx.beginPath();ctx.moveTo(-124,-49);ctx.quadraticCurveTo(-71,-88,-27,-55);ctx.moveTo(28,-55);ctx.quadraticCurveTo(75,-88,125,-49);ctx.stroke();
  if(theme==="makeup"){ctx.fillStyle="#B83D5B";ctx.fillRect(122,68,36,144);}
 }else if(theme==="massage"){
  ctx.beginPath();ctx.arc(0,-142,82,0,Math.PI*2);ctx.fill();ctx.lineWidth=85;ctx.beginPath();ctx.moveTo(-160,172);ctx.quadraticCurveTo(-65,-10,16,90);ctx.quadraticCurveTo(118,176,199,39);ctx.stroke();
 }else{ctx.lineWidth=30;ctx.beginPath();ctx.arc(0,0,207,0,Math.PI*2);ctx.moveTo(0,-134);ctx.lineTo(0,0);ctx.lineTo(120,82);ctx.stroke();}
 ctx.restore();
}



const extraCollections:Record<ExtraTheme,{title:string;slug:string;templates:{id:string;name:string}[]}>={
lashes:{title:"Ресницы",slug:"bosh-lash-trends",templates:[{id:"nude",name:"Лёгкий нюд"},{id:"romantic",name:"Романтика"},{id:"luxury",name:"Чёрный люкс"},{id:"glam",name:"Гламур"},{id:"natural",name:"Естественная красота"},{id:"soft",name:"Мягкий свет"},{id:"urban",name:"Городской шик"},{id:"evening",name:"Вечерний образ"}]},
makeup:{title:"Макияж",slug:"bosh-makeup-trends",templates:[{id:"nude",name:"Чистый нюд"},{id:"blush",name:"Розовый румянец"},{id:"luxury",name:"Вечерний люкс"},{id:"red",name:"Красный акцент"},{id:"bridal",name:"Невеста"},{id:"warm",name:"Тёплые оттенки"},{id:"urban",name:"Городской стиль"},{id:"editorial",name:"Арт-макияж"}]},
massage:{title:"Массаж",slug:"bosh-massage-calm",templates:[{id:"minimal",name:"Спокойствие"},{id:"relax",name:"Релакс"},{id:"spa",name:"SPA-ритуал"},{id:"recovery",name:"Восстановление"},{id:"natural",name:"Гармония природы"},{id:"cozy",name:"Тёплый уют"},{id:"urban",name:"Современный wellness"},{id:"luxury",name:"Премиум SPA"}]},
skin:{title:"Косметология",slug:"bosh-skin-aesthetics",templates:[{id:"clean",name:"Чистая кожа"},{id:"glow",name:"Сияние"},{id:"premium",name:"Эстетика премиум"},{id:"bold",name:"Beauty-акцент"},{id:"natural",name:"Естественность"},{id:"ritual",name:"Ритуал ухода"},{id:"urban",name:"Современная эстетика"},{id:"advanced",name:"Новая красота"}]},
barber:{title:"Барбер",slug:"bosh-barber-style",templates:[{id:"classic",name:"Классика"},{id:"gentleman",name:"Джентльмен"},{id:"luxury",name:"Чёрный премиум"},{id:"street",name:"Уличный стиль"},{id:"beard",name:"Борода и стиль"},{id:"vintage",name:"Винтаж"},{id:"urban",name:"Городской барбер"},{id:"creative",name:"Креативная стрижка"}]},
smooth:{title:"Эпиляция",slug:"bosh-smooth-skin",templates:[{id:"nude",name:"Мягкий нюд"},{id:"soft",name:"Нежность"},{id:"premium",name:"Премиум уход"},{id:"bold",name:"Яркий beauty"},{id:"natural",name:"Натуральность"},{id:"care",name:"Забота о себе"},{id:"urban",name:"Современный стиль"},{id:"luxury",name:"Эстетика тела"}]}
};
const isExtraTheme=(theme:Theme):theme is ExtraTheme=>Object.prototype.hasOwnProperty.call(extraCollections,theme);
const extraPalette=(theme:ExtraTheme,index:number)=>{
 const accents:Record<ExtraTheme,string>={lashes:"#784359",makeup:"#AA4867",massage:"#7B6856",skin:"#5B8278",barber:"#42464B",smooth:"#AD7F86"};
 return [2,3,6,7].includes(index)?{bg:"#262129",fg:"#FFF8F2",sub:"#E9D7D8",action:"#F7EFE7",actionText:"#292329"}:{bg:"#F7EEE8",fg:accents[theme],sub:accents[theme],action:accents[theme],actionText:"#FFFFFF"};
};

type BrowTemplateId = "nude" | "soft-pink" | "luxury" | "bold" | "natural" | "cozy" | "urban" | "editorial";
const BROW_COLLECTION = { name: "BOSH Brow Trends", slug: "bosh-brow-trends" } as const;
const browTemplates: {id:BrowTemplateId; name:string; top:string; bottom:string; ink:string; muted:string; button:string; buttonInk:string; skin:string; brows:string; detail:string; photo:string; photoCredit:string}[] = [
 {id:"nude",name:"Нюд",top:"#F7EEE7",bottom:"#DDC4B5",ink:"#352B2B",muted:"#6B5551",button:"#5E453D",buttonInk:"#FFFFFF",skin:"#EFC6B2",brows:"#503A34",detail:"#CBA38E",photo:"photo-1516220362602-dba5272034e7",photoCredit:"Amanda Dalbjörn"},
 {id:"soft-pink",name:"Нежный розовый",top:"#FFF1F5",bottom:"#EDBFCE",ink:"#6A304A",muted:"#98627A",button:"#B25C80",buttonInk:"#FFFFFF",skin:"#F4CCBD",brows:"#714B46",detail:"#F3AFC9",photo:"photo-1728767593550-705d6ebbf1d6",photoCredit:"Nastia Petruk"},
 {id:"luxury",name:"Премиум",top:"#211B22",bottom:"#513B43",ink:"#FFF2E2",muted:"#E8CDB9",button:"#DEBB88",buttonInk:"#362126",skin:"#DCAC94",brows:"#40272A",detail:"#C99C73",photo:"photo-1729886771704-3a9f5a613d40",photoCredit:"Nastia Petruk"},
 {id:"bold",name:"Яркий акцент",top:"#922E50",bottom:"#DD6682",ink:"#FFFFFF",muted:"#FFE2EC",button:"#FFFFFF",buttonInk:"#8A2444",skin:"#E7ACAB",brows:"#4F2032",detail:"#FFB4D0",photo:"photo-1738594399272-f05ad0a5ba8a",photoCredit:"Mohamed Marey"},
 {id:"natural",name:"Натуральный",top:"#ECE6DD",bottom:"#B7BBA9",ink:"#384037",muted:"#697166",button:"#53614C",buttonInk:"#FFFFFF",skin:"#E8C7AF",brows:"#605447",detail:"#CBD2BE",photo:"photo-1781503056004-53972080018b",photoCredit:"Emily Horne"},
 {id:"cozy",name:"Тёплый уют",top:"#E9D2BF",bottom:"#B88872",ink:"#56392F",muted:"#80574A",button:"#8A5747",buttonInk:"#FFFFFF",skin:"#EDC3A9",brows:"#584039",detail:"#E7BB9E",photo:"photo-1731284450430-484343eec537",photoCredit:"Özgür Avşar"},
 {id:"urban",name:"Городской стиль",top:"#DFD5D5",bottom:"#81808B",ink:"#26242E",muted:"#4F4A55",button:"#26242E",buttonInk:"#FFFFFF",skin:"#D9AF9C",brows:"#312D35",detail:"#C8BCC4",photo:"photo-1725886260675-7156c4b56b84",photoCredit:"Alireza Bp"},
 {id:"editorial",name:"Fashion",top:"#392239",bottom:"#A95783",ink:"#FFF1F7",muted:"#F0C4DB",button:"#F2C7DF",buttonInk:"#3F2840",skin:"#DBA7B0",brows:"#473045",detail:"#EAA9CD",photo:"photo-1569597212855-f10721b2e1ac",photoCredit:"Julia Kuzenkov"}
];
async function drawBrowBackground(ctx:CanvasRenderingContext2D,id:BrowTemplateId, imageUrl?:string, imagePosition:"top"|"center"|"bottom"="center",horizontal:"left"|"center"|"right"="center",zoom=100){
 const t=browTemplates.find(x=>x.id===id)??browTemplates[0];
 const gradient=ctx.createLinearGradient(0,0,0,1920);
 gradient.addColorStop(0,t.top);gradient.addColorStop(1,t.bottom);
 ctx.fillStyle=gradient;ctx.fillRect(0,0,1080,1920);
 // Same photographic approach as manicure, with offline fallback.
 const photo=new Image();photo.crossOrigin="anonymous";
 photo.src=imageUrl||"https://images.unsplash.com/"+t.photo+"?auto=format&fit=crop&w=1080&h=1920&q=85";
 try{await photo.decode();ctx.save();ctx.globalAlpha=.88;coverImage(ctx,photo,0,0,1080,1920,imagePosition,horizontal,zoom);ctx.restore();}
 catch{drawArt(ctx,"brows");}
 const dark=["luxury","bold","editorial"].includes(id);
 const wash=ctx.createLinearGradient(0,0,0,1920);
 if(dark){
  wash.addColorStop(0,"rgba(17,10,18,.68)");
  wash.addColorStop(.32,"rgba(17,10,18,.40)");
  wash.addColorStop(.68,"rgba(17,10,18,.61)");
  wash.addColorStop(1,"rgba(17,10,18,.84)");
 }else{
  wash.addColorStop(0,id==="soft-pink"?"rgba(255,240,246,.88)":"rgba(255,249,246,.85)");
  wash.addColorStop(.32,"rgba(255,247,244,.48)");
  wash.addColorStop(.7,"rgba(255,249,246,.73)");
  wash.addColorStop(1,"rgba(255,249,246,.94)");
 }
 ctx.fillStyle=wash;ctx.fillRect(0,0,1080,1920);
}

type HairTemplateId = "nude" | "romantic" | "brunette" | "bold" | "bridal" | "cozy" | "urban" | "chrome";
const HAIR_COLLECTION = { name: "BOSH Hair Trends", slug: "bosh-hair-trends" } as const;
const hairTemplates: {id:HairTemplateId; name:string; top:string; bottom:string; ink:string; muted:string; button:string; buttonInk:string; hair:string; highlight:string; photo:string; photoCredit:string}[] = [
 {id:"nude",name:"Nude minimal",top:"#F4ECE6",bottom:"#D7C5BC",ink:"#332D2B",muted:"#6F5C57",button:"#302C2A",buttonInk:"#FFFFFF",hair:"#836351",highlight:"#B8927D",photo:"photo-1562322140-8baeececf3df",photoCredit:"Unsplash • hair smoothing"},
 {id:"romantic",name:"Romantic soft",top:"#F6E9ED",bottom:"#E7BFC9",ink:"#5B3945",muted:"#8D5C6B",button:"#AA667F",buttonInk:"#FFFFFF",hair:"#78505B",highlight:"#C596AA",photo:"photo-1519699047748-de8e457a634e",photoCredit:"Unsplash • soft hair portrait"},
 {id:"brunette",name:"Luxury brunette",top:"#201B19",bottom:"#3A2924",ink:"#F7ECD9",muted:"#D7C0A7",button:"#D9BA86",buttonInk:"#231A15",hair:"#33201B",highlight:"#9B6950",photo:"photo-1522337360788-8b13dee7a37e",photoCredit:"Unsplash • salon hairstyle"},
 {id:"bold",name:"Bold color fashion",top:"#AE284E",bottom:"#48134B",ink:"#FFF4FA",muted:"#F6CDE5",button:"#FFFFFF",buttonInk:"#721546",hair:"#9E286A",highlight:"#F58CD7",photo:"photo-1607779097040-26e80aa78e66",photoCredit:"Unsplash • professional hair color"},
 {id:"bridal",name:"Bridal / natural",top:"#FFF9F0",bottom:"#E9DDD1",ink:"#53443C",muted:"#7B665D",button:"#DED0BC",buttonInk:"#40342D",hair:"#957B64",highlight:"#D8BE9A",photo:"photo-1560869713-7d563336772e",photoCredit:"Unsplash • hairstyle"},
 {id:"cozy",name:"Cozy seasonal",top:"#D5B59E",bottom:"#765141",ink:"#FFF7ED",muted:"#F5DCCA",button:"#FFF4E6",buttonInk:"#543629",hair:"#4E302A",highlight:"#B27D60",photo:"photo-1633681926022-84c23e8cb2d6",photoCredit:"Unsplash • hair coloring"},
 {id:"urban",name:"Urban trendy",top:"#D9D8D6",bottom:"#717078",ink:"#202127",muted:"#44464C",button:"#202127",buttonInk:"#FFFFFF",hair:"#292B31",highlight:"#A9A8B1",photo:"photo-1605497788044-5a32c7078486",photoCredit:"Unsplash • salon hair"},
 {id:"chrome",name:"Creative party / chrome",top:"#211B38",bottom:"#66417C",ink:"#FCF4FF",muted:"#E5CBF6",button:"#D7BBEF",buttonInk:"#221932",hair:"#565577",highlight:"#CFB4F4",photo:"photo-1521590832167-7bcbfaa6381f",photoCredit:"Unsplash • editorial hair studio"}
];
async function drawHairBackground(ctx:CanvasRenderingContext2D,id:HairTemplateId, imageUrl?:string, imagePosition:"top"|"center"|"bottom"="center",horizontal:"left"|"center"|"right"="center",zoom=100){
 const t=hairTemplates.find(x=>x.id===id)??hairTemplates[0];
 const g=ctx.createLinearGradient(0,0,1080,1920);g.addColorStop(0,t.top);g.addColorStop(1,t.bottom);
 ctx.fillStyle=g;ctx.fillRect(0,0,1080,1920);
 const img=new Image();img.crossOrigin="anonymous";
 img.src=imageUrl||"https://images.unsplash.com/"+t.photo+"?auto=format&fit=crop&w=1080&h=1920&q=85";
 try {
  await img.decode(); ctx.save();ctx.globalAlpha=.94;coverImage(ctx,img,0,0,1080,1920,imagePosition,horizontal,zoom);ctx.restore();
 } catch { drawArt(ctx,"hair"); }
 const dark=["brunette","bold","cozy","chrome"].includes(id);
 const wash=ctx.createLinearGradient(0,0,0,1920);
 if(dark){
  wash.addColorStop(0,"rgba(9,7,13,.69)");
  wash.addColorStop(.3,"rgba(15,12,18,.40)");
  wash.addColorStop(.7,"rgba(13,10,14,.62)");
  wash.addColorStop(1,"rgba(9,7,13,.84)");
 }else{
  wash.addColorStop(0,"rgba(255,250,246,.87)");
  wash.addColorStop(.33,"rgba(255,250,246,.49)");
  wash.addColorStop(.7,"rgba(255,250,246,.73)");
  wash.addColorStop(1,"rgba(255,250,246,.94)");
 }
 ctx.fillStyle=wash;ctx.fillRect(0,0,1080,1920);
}

type NailTemplateId="nude"|"floral"|"luxury"|"red"|"bridal"|"cozy"|"urban"|"chrome";
const nailTemplates:{id:NailTemplateId;name:string;photo:string;top:string;bottom:string;ink:string;muted:string;button:string;buttonInk:string;photoCredit:string}[]=[
{id:"nude",name:"Nude minimal",photo:"photo-1727199433231-346fd8101839",top:"#F9F1E9",bottom:"#E7C5B9",ink:"#342B2A",muted:"#695954",button:"#EAC0B4",buttonInk:"#302622",photoCredit:"Maria Lupan / Unsplash"},
{id:"floral",name:"Romantic floral",photo:"photo-1674383600495-bfa0405f3c93",top:"#FCE5EB",bottom:"#EB9CAD",ink:"#692A3F",muted:"#7D4255",button:"#D96182",buttonInk:"#FFFFFF",photoCredit:"Evita Tomševica / Unsplash"},
{id:"luxury",name:"Luxury black & gold",photo:"photo-1736434518489-0eb84070017f",top:"#181310",bottom:"#302218",ink:"#F8E8C7",muted:"#E7CFA3",button:"#E8BE74",buttonInk:"#221810",photoCredit:"Caroline Badran / Unsplash"},
{id:"red",name:"Bold red fashion",photo:"photo-1637851362556-46d07c374023",top:"#640611",bottom:"#9B0B25",ink:"#FFF5F1",muted:"#FFD1CB",button:"#EC173A",buttonInk:"#FFFFFF",photoCredit:"Reece van der Merwe / Unsplash"},
{id:"bridal",name:"Bridal / natural",photo:"photo-1680540441735-2cdc020037c8",top:"#FAF4EF",bottom:"#EDE1D9",ink:"#45382F",muted:"#786A5E",button:"#E9D2C6",buttonInk:"#3E302A",photoCredit:"Maria Sime / Unsplash"},
{id:"cozy",name:"Уютный сезонный",photo:"photo-1736434518489-0eb84070017f",top:"#3C251B",bottom:"#754A39",ink:"#FFF4EA",muted:"#EACDBB",button:"#B96E58",buttonInk:"#FFFFFF",photoCredit:"Caroline Badran / Unsplash"},
{id:"urban",name:"Urban trendy",photo:"photo-1727199433231-346fd8101839",top:"#23232A",bottom:"#4B4B54",ink:"#FFFFFF",muted:"#E4E1E8",button:"#FFFFFF",buttonInk:"#24232A",photoCredit:"Maria Lupan / Unsplash"},
{id:"chrome",name:"Creative chrome / party",photo:"photo-1684609365994-a144ee021c88",top:"#211239",bottom:"#50317C",ink:"#FDEBFF",muted:"#DEB9FE",button:"#CDA0FA",buttonInk:"#26113C",photoCredit:"Noel Oviedo / Unsplash"}
];
function coverImage(ctx:CanvasRenderingContext2D,img:HTMLImageElement,x:number,y:number,w:number,h:number,position:"top"|"center"|"bottom"="center",horizontal:"left"|"center"|"right"="center",zoom=100){
const magnification=Math.max(1,Math.min(1.5,zoom/100));
const scale=Math.max(w/img.naturalWidth,h/img.naturalHeight)*magnification;
const sw=w/scale,sh=h/scale;
const alignX=horizontal==="left"?0:horizontal==="right"?1:.5;
const alignY=position==="top"?0:position==="bottom"?1:.5;
ctx.drawImage(img,(img.naturalWidth-sw)*alignX,(img.naturalHeight-sh)*alignY,sw,sh,x,y,w,h);
}
async function drawExtraBackground(ctx:CanvasRenderingContext2D,theme:ExtraTheme,index:number,url?:string,imagePosition:"top"|"center"|"bottom"="center",horizontal:"left"|"center"|"right"="center",zoom=100){
 const p=extraPalette(theme,index);
 ctx.fillStyle=p.bg;ctx.fillRect(0,0,1080,1920);
 if(url){
  const image=new Image();image.crossOrigin="anonymous";image.src=url;
  try{await image.decode();coverImage(ctx,image,0,0,1080,1920,imagePosition,horizontal,zoom);}
  catch{drawArt(ctx,theme);}
 }else drawArt(ctx,theme);
 const dark=[2,3,6,7].includes(index),wash=ctx.createLinearGradient(0,0,0,1920);
 wash.addColorStop(0,dark?"rgba(15,10,17,.76)":"rgba(255,250,247,.88)");
 wash.addColorStop(.4,dark?"rgba(15,10,17,.40)":"rgba(255,250,247,.40)");
 wash.addColorStop(1,dark?"rgba(15,10,17,.84)":"rgba(255,250,247,.93)");
 ctx.fillStyle=wash;ctx.fillRect(0,0,1080,1920);
}
async function drawNailBackground(ctx:CanvasRenderingContext2D,template:NailTemplateId,imageUrl?:string, imagePosition:"top"|"center"|"bottom"="center",horizontal:"left"|"center"|"right"="center",zoom=100){
const t=nailTemplates.find(x=>x.id===template)??nailTemplates[0];
const gradient=ctx.createLinearGradient(0,0,0,1920);
gradient.addColorStop(0,t.top);gradient.addColorStop(1,t.bottom);
ctx.fillStyle=gradient;ctx.fillRect(0,0,1080,1920);
const image=new Image();image.crossOrigin="anonymous";
image.src=imageUrl||"https://images.unsplash.com/"+t.photo+"?auto=format&fit=crop&w=1080&h=1920&q=85";
try{
 await new Promise<void>((resolve,reject)=>{image.onload=()=>resolve();image.onerror=()=>reject(new Error("Image not available"));});
 ctx.save();ctx.globalAlpha=t.id==="urban"?.82:.87;coverImage(ctx,image,0,0,1080,1920,imagePosition,horizontal,zoom);ctx.restore();
}catch{ drawArt(ctx,"nails"); /* Retain an illustrated fallback offline. */ }
const wash=ctx.createLinearGradient(0,0,0,1920);
if(["luxury","red","cozy","urban","chrome"].includes(t.id)){
 wash.addColorStop(0,"rgba(0,0,0,.64)");wash.addColorStop(.35,"rgba(0,0,0,.40)");wash.addColorStop(.72,"rgba(0,0,0,.55)");wash.addColorStop(1,"rgba(0,0,0,.78)");
}else{
 wash.addColorStop(0,"rgba(255,249,246,.83)");wash.addColorStop(.33,"rgba(255,248,243,.45)");wash.addColorStop(.70,"rgba(255,248,243,.69)");wash.addColorStop(1,"rgba(255,248,243,.92)");
}
ctx.fillStyle=wash;ctx.fillRect(0,0,1080,1920);
}

const WIDTH = 1080;
const HEIGHT = 1920;

function wrappedText(ctx: CanvasRenderingContext2D, value: string, maxWidth: number, maxLines: number) {
    const words = value.trim().split(/\s+/);
    const result: string[] = [];
    let line = "";
    for (const word of words) {
        const attempt = line ? line + " " + word : word;
        if (ctx.measureText(attempt).width > maxWidth && line) {
            result.push(line);
            line = word;
        } else line = attempt;
    }
    if (line) result.push(line);
    const clipped = result.slice(0, maxLines);
    if (result.length > maxLines && clipped.length) {
        let last = clipped[clipped.length - 1];
        while (last && ctx.measureText(last + "…").width > maxWidth) last = last.slice(0, -1);
        clipped[clipped.length - 1] = last + "…";
    }
    return clipped;
}
async function drawCategoryStory(canvas:HTMLCanvasElement,master:Master,service:Service,date:string,time:string,link:string,categoryName:string,template:CategoryTemplate){
 const ctx=canvas.getContext("2d");if(!ctx)throw new Error("Canvas недоступен");
 const design=categoryLook[template.id]||categoryLook.editorial;
 const g=ctx.createLinearGradient(0,0,1080,1920);
 g.addColorStop(0,design.bg);g.addColorStop(1,design.end);
 ctx.fillStyle=g;ctx.fillRect(0,0,1080,1920);
 if(template.image_url){
  const img=new Image();img.crossOrigin="anonymous";img.src=template.image_url;
  try{
   await img.decode();
   coverImage(ctx,img,0,0,1080,1920,template.image_position,template.image_horizontal,template.image_zoom);
  }catch{ /* Retain the category's readable gradient if the uploaded image is unavailable. */ }
 }
 const fg=template.text_color&&/^#[A-Fa-f0-9]{6}$/.test(template.text_color)?template.text_color:design.fg;
 const rgb=[1,3,5].map(i=>parseInt(fg.slice(i,i+2),16));
 const light=(rgb[0]*299+rgb[1]*587+rgb[2]*114)/1000>145;
 const wash=ctx.createLinearGradient(0,0,0,1920);
 const dark=light?"0,0,0":"255,255,255";
 wash.addColorStop(0,`rgba(${dark},.66)`);
 wash.addColorStop(.5,`rgba(${dark},.48)`);
 wash.addColorStop(1,`rgba(${dark},.75)`);
 if(template.image_url){ctx.fillStyle=wash;ctx.fillRect(0,0,1080,1920);}
 const shade=Math.min(40,Math.max(0,Number(template.shade)||0));
 if(shade){ctx.fillStyle=`rgba(${dark},${shade/100})`;ctx.fillRect(0,0,1080,1920);}
 const font=fontFace(template.font),size=Math.max(80,Math.min(120,Number(template.scale)||100))/100;
 ctx.textBaseline="top";ctx.fillStyle=fg;
 ctx.font=`bold 46px ${font}`;
 ctx.fillText("BOSH",108,122);
 ctx.font=`500 36px ${font}`;
 wrappedText(ctx,master.display_name,840,2).forEach((line,i)=>ctx.fillText(line,108,225+i*46,864));
 ctx.font=`600 38px ${font}`;
 ctx.fillText("ЕСТЬ СВОБОДНОЕ ВРЕМЯ",108,560,864);
 ctx.font=`bold ${Math.round(140*size)}px ${font}`;
 wrappedText(ctx,categoryName,850,3).forEach((line,i)=>ctx.fillText(line,108,650+i*180,864));
 ctx.font=`600 50px ${font}`;
 wrappedText(ctx,service.name,850,2).forEach((line,i)=>ctx.fillText(line,108,1210+i*60,864));
 ctx.font=`bold 96px ${font}`;
 ctx.fillText(time,108,1360,864);
 const niceDate=new Date(date+"T12:00:00Z").toLocaleDateString("ru-RU",{timeZone:"UTC",day:"numeric",month:"long"});
 ctx.font=`500 46px ${font}`;
 ctx.fillText(niceDate+" · "+service.price_som.toLocaleString("ru-RU")+" сом",108,1495,864);
 ctx.fillStyle=design.action;
 ctx.beginPath();ctx.roundRect(108,1560,864,112,55);ctx.fill();
 ctx.fillStyle=design.actionText;ctx.font=`bold 46px ${font}`;
 ctx.fillText("Записаться",160,1590,760);
 ctx.fillStyle=fg;ctx.font=`500 30px ${font}`;
 const url=new URL(link);ctx.fillText(url.host+url.pathname,108,1740,864);
}
export async function drawStory(canvas: HTMLCanvasElement, master: Master, service: Service, date: string, time: string, style: Style, link: string, theme: Theme, nailTemplate: NailTemplateId, hairTemplate: HairTemplateId, browTemplate: BrowTemplateId, extraId:string, catalog:StoryCatalog,categoryTemplate?:CategoryTemplate,categoryName?:string) {
    canvas.width = WIDTH;
    canvas.height = HEIGHT;
    if(theme==="category" && categoryTemplate){await drawCategoryStory(canvas,master,service,date,time,link,categoryName||"Услуги",categoryTemplate);return;}
    const ctx = canvas.getContext("2d");
    if (!ctx) throw new Error("Браузер не поддерживает создание изображений.");
    const design = nailTemplates.find(t=>t.id===nailTemplate)??nailTemplates[0];
    const hairDesign = hairTemplates.find(t=>t.id===hairTemplate)??hairTemplates[0];
    const browDesign = browTemplates.find(t=>t.id===browTemplate)??browTemplates[0];
    const templateId=theme==="nails"?nailTemplate:theme==="hair"?hairTemplate:theme==="brows"?browTemplate:null;
    const extraTheme=isExtraTheme(theme)?theme:null;
    const extraIndex=extraTheme?Math.max(0,extraCollections[extraTheme].templates.findIndex(t=>t.id===extraId)):0;
    const editor=extraTheme?catalog[extraTheme]?.[extraId]:templateId && (theme==="nails"||theme==="hair"||theme==="brows")?catalog[theme]?.[templateId]:undefined;
    const family=fontFace(editor?.font);
    const headingSize=Math.max(80,Math.min(120,editor?.scale??100))/100;
    const p = extraTheme ? extraPalette(extraTheme,extraIndex) : theme==="brows" ? {bg:browDesign.top,fg:browDesign.ink,sub:browDesign.muted,action:browDesign.button,actionText:browDesign.buttonInk} : theme==="hair" ? {bg:hairDesign.top,fg:hairDesign.ink,sub:hairDesign.muted,action:hairDesign.button,actionText:hairDesign.buttonInk} : theme==="nails" ? {bg:design.top,fg:design.ink,sub:design.muted,action:design.button,actionText:design.buttonInk} : palettes[style];
    if(theme==="nails") await drawNailBackground(ctx,nailTemplate,editor?.image_url??undefined,editor?.image_position,editor?.image_horizontal,editor?.image_zoom);
    else if(theme==="hair") await drawHairBackground(ctx,hairTemplate,editor?.image_url??undefined,editor?.image_position,editor?.image_horizontal,editor?.image_zoom);
    else if(theme==="brows") await drawBrowBackground(ctx,browTemplate,editor?.image_url??undefined,editor?.image_position,editor?.image_horizontal,editor?.image_zoom);
    else if(extraTheme) await drawExtraBackground(ctx,extraTheme,extraIndex,editor?.photo_url,editor?.image_position,editor?.image_horizontal,editor?.image_zoom);
    else {ctx.fillStyle=p.bg;ctx.fillRect(0,0,WIDTH,HEIGHT);drawArt(ctx,theme);}
    if(editor?.text_color && /^#[0-9a-fA-F]{6}$/.test(editor.text_color)){p.fg=editor.text_color;p.sub=editor.text_color;}
    const shade=Math.min(40,Math.max(0,Number(editor?.shade)||0));
    if(shade>0){
        const foreground=p.fg.replace("#","");
        const rgb=/^[0-9a-fA-F]{6}$/.test(foreground)
            ? [0,2,4].map(i=>parseInt(foreground.slice(i,i+2),16)) : [255,255,255];
        const luminance=(rgb[0]*299+rgb[1]*587+rgb[2]*114)/1000;
        ctx.fillStyle=luminance>145?`rgba(0,0,0,${shade/100})`:`rgba(255,255,255,${shade/100})`;
        ctx.fillRect(0,0,WIDTH,HEIGHT);
    }
    const margin = 100;
    ctx.textBaseline = "top";
    ctx.fillStyle = p.fg;
    ctx.font = `bold 45px ${family}`;
    // Render the real BOSH mark next to the brand name (fallback keeps the wordmark).
    const brandIcon=new Image();
    brandIcon.src="/brand/bosh-mark-v2.svg";
    try {
        await brandIcon.decode();
        ctx.fillStyle="#FFFFFF";ctx.beginPath();ctx.arc(margin+24,158,33,0,Math.PI*2);ctx.fill();
        ctx.drawImage(brandIcon,margin+2,136,44,44);
    } catch { /* Keep the wordmark if the icon is unavailable. */ }
    ctx.fillStyle=p.fg;
    ctx.fillText("BOSH", margin+75, 132);
    ctx.font = `500 36px ${family}`;
    const mast = wrappedText(ctx, master.display_name, 650, 2);
    mast.forEach((line, i) => ctx.fillText(line, margin, 235 + i * 48, WIDTH-margin*2));
    ctx.fillStyle = p.sub;
    ctx.font = `500 38px ${family}`;
    ctx.fillText("ЕСТЬ СВОБОДНОЕ ВРЕМЯ", margin, 580, WIDTH-margin*2);
    ctx.fillStyle = p.fg;
    ctx.font = `bold ${Math.round(94*headingSize)}px ${family}`;
    wrappedText(ctx, theme==="nails" ? "Маникюр" : theme==="hair" ? "Волосы" : theme==="brows" ? "Брови" : extraTheme ? themes[extraTheme] : "Освободилось окно", 870, 3).forEach((line, i) => ctx.fillText(line, margin, 690 + i * 112, WIDTH-margin*2));
    ctx.font = `600 54px ${family}`;
    wrappedText(ctx, service.name, 860, 2).forEach((line, i) => ctx.fillText(line, margin, 1030 + i * 68, WIDTH-margin*2));
    const niceDate = new Date(date + "T12:00:00Z").toLocaleDateString("ru-RU", { timeZone: "UTC", day: "numeric", month: "long" });
    ctx.font = `bold 138px ${family}`;
    ctx.fillText(time, margin, 1230, WIDTH-margin*2);
    ctx.font = `500 49px ${family}`;
    ctx.fillText(niceDate + " · " + service.price_som.toLocaleString("ru-RU") + " сом", margin, 1400, WIDTH-margin*2);
    ctx.fillStyle = p.action;
    ctx.beginPath();
    ctx.roundRect(margin, 1570, WIDTH - margin * 2, 108, 54);
    ctx.fill();
    ctx.fillStyle = p.actionText;
    ctx.font = `600 48px ${family}`;
    ctx.fillText("Записаться", margin + 55, 1594);
    ctx.fillStyle = p.sub;
    ctx.font = `500 28px ${family}`;
    const url = new URL(link);
    const displayLink = url.host + url.pathname;
    ctx.fillText(displayLink.length > 46 ? displayLink.slice(0, 43) + "…" : displayLink, margin, 1740, WIDTH-margin*2);
}
function toBlob(canvas: HTMLCanvasElement): Promise<Blob> {
    return new Promise((resolve, reject) => canvas.toBlob(blob => blob ? resolve(blob) : reject(new Error("Не удалось сохранить PNG.")), "image/png"));
}
export default function StoryCreator({ master, services, onClose, initialDate, initialTime, initialService }: {
    master: Master; services: Service[]; onClose: () => void;
    initialDate?: string; initialTime?: string; initialService?: number;
}) {
    const activeServices = services.filter(s => s.is_active);

    const [serviceId, setServiceId] = useState(initialService && activeServices.some(s => s.id === initialService) ? initialService : activeServices[0]?.id || 0);
    const [date, setDate] = useState(initialDate || new Intl.DateTimeFormat("en-CA", { timeZone: master.timezone, year: "numeric", month: "2-digit", day: "2-digit" }).format(new Date()));
    const [time, setTime] = useState(initialTime || "12:00");
    const [style] = useState<Style>(master.story_style && master.story_style in palettes ? master.story_style as Style : "minimal");
    const [error, setError] = useState("");
    const [busy, setBusy] = useState(false);
    const [mobileStoryView, setMobileStoryView] = useState<"edit" | "preview">("edit");
    const [catalog] = useState<StoryCatalog>({});
    const [headlineOverride,setHeadlineOverride]=useState<string|null>(null);
    const [categoryCatalog,setCategoryCatalog]=useState<CategoryResponse>({category:null,templates:[]});
    const [categoryPair,setCategoryPair]=useState(()=>{
        const saved=master.story_template?.split(":");
        return saved?.[0]==="category" ? [saved[1],saved[2]] : [master.industry||"",master.activity||""];
    });
    useEffect(()=>{let live=true;axios.get<CategoryResponse>("/stories/category/catalog",{params:{industry:categoryPair[0],activity:categoryPair[1]}}).then(({data})=>{if(live)setCategoryCatalog(data);}).catch(()=>{if(live)setError("Не удалось загрузить шаблоны категории. Попробуйте ещё раз.");});return()=>{live=false;};},[categoryPair[0],categoryPair[1]]);
    const [prepared, setPrepared] = useState<File | null>(null);
    const canvas = useRef<HTMLCanvasElement>(null);

    const [categorySelection,setCategorySelection]=useState<string>(()=>{
      const parts=master.story_template?.split(":");
      return parts?.[0]==="category"?parts[3]:"editorial";
    });
    const nailTemplate:NailTemplateId="nude";
    const hairTemplate:HairTemplateId="nude";
    const browTemplate:BrowTemplateId="soft-pink";
    const [savingTemplate,setSavingTemplate]=useState(false);
    const [savedTemplate,setSavedTemplate]=useState<string|null>(master.story_template??null);
    const renderSequence=useRef(0);
    const service = activeServices.find(s => s.id === serviceId);
    const hasCategory=!!(categoryCatalog.category && categoryCatalog.category.industry===categoryPair[0] && categoryCatalog.category.activity===categoryPair[1] && categoryCatalog.templates.length);
    const theme:Theme="category";
    const headline=headlineOverride ?? categoryCatalog.category?.name ?? "";
    const categoryTemplate=hasCategory?categoryCatalog.templates.find(t=>t.id===categorySelection)??categoryCatalog.templates[0]:undefined;
    const extraId="";
    const link = new URL(`/m/${encodeURIComponent(master.slug)}?date=${encodeURIComponent(date)}&service=${serviceId}&time=${encodeURIComponent(time)}`, window.location.origin).toString();

    useEffect(() => {
        const sequence=++renderSequence.current;
        setPrepared(null);
        setError("");
        if ((theme!=="category" || hasCategory) && service && canvas.current && /^\d{4}-\d{2}-\d{2}$/.test(date) && /^\d{2}:\d{2}$/.test(time)) {
            const preview=canvas.current;
            // A temporary canvas prevents late image loads from overwriting a newer template.
            const offscreen=document.createElement("canvas");
            void drawStory(offscreen,master,service,date,time,style,link,theme,nailTemplate,hairTemplate,browTemplate,extraId,catalog,categoryTemplate,headline)
                .then(()=>{if(sequence!==renderSequence.current)return;const c=preview.getContext("2d");if(c){preview.width=offscreen.width;preview.height=offscreen.height;c.drawImage(offscreen,0,0);}})
                .catch(()=>{if(sequence===renderSequence.current)setError("Не удалось отобразить сторис.");});
        }
    }, [master, service, date, time, style, link, theme, nailTemplate, hairTemplate, browTemplate, extraId, catalog, categoryTemplate, headline, hasCategory]);
    useEffect(() => {
        const handleKey = (event: KeyboardEvent) => { if (event.key === "Escape") onClose(); };
        window.addEventListener("keydown", handleKey);
        return () => window.removeEventListener("keydown", handleKey);
    }, [onClose]);
    const saveTemplate=async(collection:string,id:string)=>{
        const selected=collection+":"+id;
        setSavedTemplate(null);
        setSavingTemplate(true);
        try{
            const {data}=await axios.post<{template:string}>("/stories/style",{template:selected});
            setSavedTemplate(data.template);
        }catch{
            setError("Не удалось сохранить выбранный шаблон. Проверьте соединение.");
        }finally{setSavingTemplate(false);}
    };
    async function prepare() {
        if (!service || !canvas.current || !hasCategory || !headline.trim()) return;
        setBusy(true);
        setError("");
        setPrepared(null);
        try {
            const response = await axios.get<{available: boolean; reason?: string}>("/stories/availability", {params: {service: service.id, date, time}});
            if (!response.data.available) throw new Error(response.data.reason || "Это время сейчас недоступно.");
            await drawStory(canvas.current, master, service, date, time, style, link, theme, nailTemplate, hairTemplate, browTemplate, extraId, catalog, categoryTemplate, headline);
            const blob = await toBlob(canvas.current);
            if(blob.size===0) throw new Error("PNG пустой.");
            setPrepared(new File([blob], `bosh-${date}-${time.replace(":", "-")}.png`, { type: "image/png" }));
        } catch (e) {
            setError(e instanceof Error ? e.message : "Не удалось создать сторис. Попробуйте снова.");
        } finally { setBusy(false); }
    }
    function download() {
        if (!prepared) return;
        const url = URL.createObjectURL(prepared);
        const a = document.createElement("a");
        a.href = url; a.download = prepared.name;
        document.body.append(a); a.click(); a.remove();
        window.setTimeout(() => URL.revokeObjectURL(url), 10000);
    }
    async function share() {
        if (!prepared) return;
        if (!navigator.canShare?.({files: [prepared]}) || !navigator.share) {
            download();
            setError("Системная отправка недоступна. PNG сохранён на устройство.");
            return;
        }
        try {
            await navigator.share({files: [prepared], title: "Свободное время"});
        } catch (e) {
            if ((e as DOMException).name !== "AbortError") setError("Не удалось поделиться. Используйте «Скачать PNG».");
        }
    }
    return <div className="story-overlay" role="presentation" onMouseDown={e => { if (e.target === e.currentTarget) onClose(); }}>
        <section className={`story-dialog panel story-view-${mobileStoryView}`} role="dialog" aria-modal="true" aria-label="Создать сторис">
            <div className="row between"><h2>Сторис об окне</h2><CloseButton onClick={onClose} label="Закрыть" /></div>
            <div className="story-mobile-tabs" role="group" aria-label="Режим просмотра сторис">
                <button type="button" className={mobileStoryView === "edit" ? "selected" : ""} aria-pressed={mobileStoryView === "edit"} onClick={() => setMobileStoryView("edit")}>Настройки</button>
                <button type="button" className={mobileStoryView === "preview" ? "selected" : ""} aria-pressed={mobileStoryView === "preview"} onClick={() => setMobileStoryView("preview")}>Превью</button>
            </div>
            <div className="story-grid">
                <div className="story-fields">
                    <label>Услуга<select value={serviceId} onChange={e => setServiceId(Number(e.target.value))}>{activeServices.map(s => <option key={s.id} value={s.id}>{s.name} · {s.price_som.toLocaleString("ru-RU")} сом</option>)}</select></label>
                    <div className="story-input-row">
                        <label>Дата<input type="date" value={date} onChange={e => setDate(e.target.value)} /></label>
                        <label>Время<input type="time" step="1800" value={time} onChange={e => setTime(e.target.value)} /></label>
                    </div>
                    <label>Категория шаблонов<select value={categoryPair[0]} onChange={e=>{const group=categoryCatalog.groups?.find(g=>g.id===e.target.value);if(group){setCategoryPair([group.id,Object.keys(group.activities)[0]]);setHeadlineOverride(null);setCategorySelection("editorial");}}}>
                        <option value="" disabled>Выберите категорию</option>
                        {categoryCatalog.groups?.map(g=><option key={g.id} value={g.id}>{g.name}</option>)}
                    </select></label>
                    {categoryPair[0] && <label>Подборка<select value={categoryPair[1]} onChange={e=>{setCategoryPair([categoryPair[0],e.target.value]);setHeadlineOverride(null);setCategorySelection("editorial");}}>
                        {Object.entries(categoryCatalog.groups?.find(g=>g.id===categoryPair[0])?.activities||{}).map(([id,name])=><option key={id} value={id}>{name}</option>)}
                    </select></label>}
                    <label>Текст на картинке<textarea value={headline} maxLength={80} rows={3} onChange={e=>setHeadlineOverride(e.target.value)} placeholder="Напишите свой текст" /></label>
                    {theme==="category" && hasCategory && <div>
                        <p className="muted small">{categoryCatalog.category?.name} · 8 вариантов оформления</p>
                        <div className="story-nail-templates" role="group" aria-label="Шаблоны моей категории">
                        {categoryCatalog.templates.map(t=><button type="button" key={t.id} aria-pressed={categoryTemplate?.id===t.id}
                          className={categoryTemplate?.id===t.id?"story-nail-template selected":"story-nail-template"}
                          disabled={savingTemplate}
                          onClick={()=>{setCategorySelection(t.id);void saveTemplate("category:"+categoryCatalog.category!.industry+":"+categoryCatalog.category!.activity,t.id);}}>
                          <span className="story-nail-thumb" style={{backgroundImage:t.image_url?`linear-gradient(180deg,transparent,rgba(0,0,0,.45)),url("${t.image_url}")`:undefined,backgroundColor:categoryLook[t.id]?.bg||"#e9e5df"}}/>
                          <span>{t.name}</span>
                        </button>)}
                        </div>
                        <p className="muted small" role="status">{savingTemplate?"Сохраняем шаблон…":savedTemplate==="category:"+categoryCatalog.category?.industry+":"+categoryCatalog.category?.activity+":"+categoryTemplate?.id?"Шаблон сохранён":""}</p>
                    </div>}
                    <p className="muted small">Сторис создаётся в размере 1080 × 1920. Ссылка на запись подготовлена отдельно для стикера Instagram.</p>
                    <div className="row wrap">
                        <button type="button" className="primary" disabled={busy || !service || !hasCategory || !headline.trim()} onClick={() => void prepare()}>{busy ? "Проверяем время…" : "Создать сторис"}</button>
                        {prepared && <button type="button" className="ghost" onClick={download}>Скачать PNG</button>}
                        {prepared && <button type="button" className="ghost" onClick={() => void share()}>Поделиться</button>}
                        {prepared && <button type="button" className="ghost" onClick={() => navigator.clipboard.writeText(link).then(() => setError("Ссылка скопирована.")).catch(() => setError("Не удалось скопировать ссылку."))}>Копировать ссылку</button>}
                    </div>
                    {error && <p role="status" className="muted small">{error}</p>}
                </div>
                <canvas ref={canvas} className="story-preview" aria-label="Предпросмотр сторис" />
            </div>
        </section>
    </div>;
}
