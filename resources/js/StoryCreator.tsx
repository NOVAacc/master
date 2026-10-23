import axios from "axios";
import { useEffect, useRef, useState } from "react";
import CloseButton from "./CloseButton";
import type { Master, Service } from "./types";

type Style = "minimal" | "dark" | "soft" | "accent" | "premium";
const palettes: Record<Style, { name: string; bg: string; fg: string; sub: string; action: string; actionText: string }> = {
    minimal: { name: "Минимализм", bg: "#F5F4F1", fg: "#202020", sub: "#76716D", action: "#202020", actionText: "#FFFFFF" },
    dark: { name: "Графит", bg: "#1B1D22", fg: "#FFFFFF", sub: "#B8BAC0", action: "#FFFFFF", actionText: "#1B1D22" },
    soft: { name: "Нежный", bg: "#F8E3E8", fg: "#6C304B", sub: "#9E6B80", action: "#6C304B", actionText: "#FFFFFF" },
    accent: { name: "Акцент", bg: "#CE303E", fg: "#FFFFFF", sub: "#FBE5E7", action: "#FFFFFF", actionText: "#AE2632" },
    premium: { name: "Премиум", bg: "#26342D", fg: "#F3EAD7", sub: "#C9BBA3", action: "#DDC390", actionText: "#26342D" },
};

type Theme = "nails"|"hair"|"barber"|"brows"|"makeup"|"massage"|"skin"|"general";
const themes: Record<Theme,string> = {nails:"Маникюр",hair:"Волосы",barber:"Барбер",brows:"Брови",makeup:"Макияж",massage:"Массаж",skin:"Косметология",general:"Универсальный"};
const rules: [Theme,string[]][]=[
 ["nails",["маникюр","педикюр","ногт","nail","шеллак","гель лак"]],
 ["brows",["бров","brow","окрашивание бровей","коррекция бровей"]],
 ["makeup",["визаж","макияж","makeup","мейкап"]],
 ["massage",["массаж","спа","spa","релакс"]],
 ["skin",["косметолог","пилинг","чистка лица","эпиляц","депиляц"]],
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
 const c:Record<Theme,string>={nails:"#C73855",hair:"#65413D",barber:"#353C44",brows:"#6D453D",makeup:"#B84963",massage:"#A27868",skin:"#BB8B7A",general:"#A5636A"};
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



type BrowTemplateId = "nude" | "soft-pink" | "luxury" | "bold" | "natural" | "cozy" | "urban" | "editorial";
const BROW_COLLECTION = { name: "BOSH Brow Trends", slug: "bosh-brow-trends" } as const;
const browTemplates: {id:BrowTemplateId; name:string; top:string; bottom:string; ink:string; muted:string; button:string; buttonInk:string; skin:string; brows:string; detail:string}[] = [
 {id:"nude",name:"Нюд",top:"#F7EEE7",bottom:"#DDC4B5",ink:"#352B2B",muted:"#6B5551",button:"#5E453D",buttonInk:"#FFFFFF",skin:"#EFC6B2",brows:"#503A34",detail:"#CBA38E"},
 {id:"soft-pink",name:"Нежный розовый",top:"#FFF1F5",bottom:"#EDBFCE",ink:"#6A304A",muted:"#98627A",button:"#B25C80",buttonInk:"#FFFFFF",skin:"#F4CCBD",brows:"#714B46",detail:"#F3AFC9"},
 {id:"luxury",name:"Премиум",top:"#211B22",bottom:"#513B43",ink:"#FFF2E2",muted:"#E8CDB9",button:"#DEBB88",buttonInk:"#362126",skin:"#DCAC94",brows:"#40272A",detail:"#C99C73"},
 {id:"bold",name:"Яркий акцент",top:"#922E50",bottom:"#DD6682",ink:"#FFFFFF",muted:"#FFE2EC",button:"#FFFFFF",buttonInk:"#8A2444",skin:"#E7ACAB",brows:"#4F2032",detail:"#FFB4D0"},
 {id:"natural",name:"Натуральный",top:"#ECE6DD",bottom:"#B7BBA9",ink:"#384037",muted:"#697166",button:"#53614C",buttonInk:"#FFFFFF",skin:"#E8C7AF",brows:"#605447",detail:"#CBD2BE"},
 {id:"cozy",name:"Тёплый уют",top:"#E9D2BF",bottom:"#B88872",ink:"#56392F",muted:"#80574A",button:"#8A5747",buttonInk:"#FFFFFF",skin:"#EDC3A9",brows:"#584039",detail:"#E7BB9E"},
 {id:"urban",name:"Городской стиль",top:"#DFD5D5",bottom:"#81808B",ink:"#26242E",muted:"#4F4A55",button:"#26242E",buttonInk:"#FFFFFF",skin:"#D9AF9C",brows:"#312D35",detail:"#C8BCC4"},
 {id:"editorial",name:"Fashion",top:"#392239",bottom:"#A95783",ink:"#FFF1F7",muted:"#F0C4DB",button:"#F2C7DF",buttonInk:"#3F2840",skin:"#DBA7B0",brows:"#473045",detail:"#EAA9CD"}
];
function drawBrowBackground(ctx:CanvasRenderingContext2D,id:BrowTemplateId){
 const t=browTemplates.find(x=>x.id===id)??browTemplates[0];
 const g=ctx.createLinearGradient(0,0,1050,1920);g.addColorStop(0,t.top);g.addColorStop(1,t.bottom);
 ctx.fillStyle=g;ctx.fillRect(0,0,1080,1920);
 // Large editorial close-up of eyebrows and eyes; all vectors are locally drawn, so PNG export works offline.
 ctx.save();ctx.translate(740,850);if(id==="urban")ctx.rotate(.09);if(id==="editorial")ctx.rotate(-.13);
 ctx.fillStyle=t.skin;
 ctx.beginPath();ctx.ellipse(0,0,350,490,-.08,0,Math.PI*2);ctx.fill();
 const gl=ctx.createRadialGradient(-100,-180,20,0,0,430);
 gl.addColorStop(0,"rgba(255,255,255,.40)");gl.addColorStop(1,"rgba(102,38,40,.13)");
 ctx.fillStyle=gl;ctx.beginPath();ctx.ellipse(0,0,350,490,-.08,0,Math.PI*2);ctx.fill();
 // Eyebrow hair with tapered natural arch
 for(const side of [-1,1]){
  const cx=side*154;
  ctx.save();ctx.translate(cx,-150);if(side===1)ctx.scale(-1,1);
  ctx.fillStyle=t.brows;ctx.beginPath();ctx.moveTo(-136,14);
  ctx.bezierCurveTo(-94,-31,-19,-49,95,-11);
  ctx.bezierCurveTo(65,-7,24,-15,-16,-6);
  ctx.bezierCurveTo(-65,3,-91,24,-136,14);ctx.fill();
  ctx.strokeStyle=t.detail;ctx.lineWidth=2;ctx.globalAlpha=.42;
  for(let i=0;i<13;i++){const x=-104+i*14;ctx.beginPath();ctx.moveTo(x,-8);ctx.quadraticCurveTo(x+12,-26,x+20,-19);ctx.stroke();}
  ctx.restore();
  // Eye and lashes below the brow
  const cy=side===1?18:14;
  ctx.strokeStyle=t.brows;ctx.lineWidth=10;ctx.lineCap="round";
  ctx.beginPath();ctx.moveTo(cx-95,cy);ctx.quadraticCurveTo(cx,cy-48,cx+93,cy);ctx.stroke();
  ctx.lineWidth=4;ctx.beginPath();ctx.moveTo(cx-70,cy+8);ctx.quadraticCurveTo(cx,cy+57,cx+75,cy+8);ctx.stroke();
  ctx.fillStyle=t.brows;ctx.beginPath();ctx.arc(cx,cy+2,21,0,Math.PI*2);ctx.fill();
  ctx.fillStyle="#FFFFFF";ctx.globalAlpha=.7;ctx.beginPath();ctx.arc(cx+5,cy-6,6,0,Math.PI*2);ctx.fill();ctx.globalAlpha=1;
 }
 ctx.strokeStyle=t.detail;ctx.lineWidth=12;ctx.globalAlpha=.38;
 ctx.beginPath();ctx.moveTo(-18,-25);ctx.bezierCurveTo(-52,100,-96,215,13,218);ctx.stroke();ctx.globalAlpha=1;
 ctx.restore();
 if(id==="soft-pink"){
  // Gentle floating petals and a fine blush ring, behind text-safe left area.
  ctx.strokeStyle="rgba(255,255,255,.55)";ctx.lineWidth=5;
  ctx.beginPath();ctx.arc(855,720,310,Math.PI*.92,Math.PI*1.72);ctx.stroke();
  ctx.fillStyle="rgba(255,255,255,.48)";
  for(const [x,y,r] of [[960,380,31],[780,410,19],[997,1160,23],[650,360,18]]) {
    ctx.save();ctx.translate(x,y);ctx.rotate(.45);ctx.beginPath();ctx.ellipse(0,0,r,r*.48,0,0,Math.PI*2);ctx.fill();ctx.restore();
  }
 }
 if(id==="luxury"){ctx.strokeStyle="rgba(222,187,136,.42)";ctx.lineWidth=4;ctx.strokeRect(55,65,970,1780);}
 if(id==="editorial"){ctx.strokeStyle="rgba(255,226,243,.46)";ctx.lineWidth=14;ctx.beginPath();ctx.arc(970,1040,325,.4,2.7);ctx.stroke();}
 const wash=ctx.createLinearGradient(0,0,0,1920);
 const dark=["luxury","bold","editorial"].includes(id);
 wash.addColorStop(0,dark?"rgba(0,0,0,.27)":"rgba(255,255,255,.32)");
 wash.addColorStop(.45,dark?"rgba(0,0,0,.15)":"rgba(255,255,255,.08)");
 wash.addColorStop(.7,dark?"rgba(0,0,0,.38)":"rgba(255,255,255,.28)");
 wash.addColorStop(1,dark?"rgba(0,0,0,.72)":"rgba(255,255,255,.74)");
 ctx.fillStyle=wash;ctx.fillRect(0,0,1080,1920);
}

type HairTemplateId = "nude" | "romantic" | "brunette" | "bold" | "bridal" | "cozy" | "urban" | "chrome";
const HAIR_COLLECTION = { name: "BOSH Hair Trends", slug: "bosh-hair-trends" } as const;
const hairTemplates: {id:HairTemplateId; name:string; top:string; bottom:string; ink:string; muted:string; button:string; buttonInk:string; hair:string; highlight:string}[] = [
 {id:"nude",name:"Nude minimal",top:"#F4ECE6",bottom:"#D7C5BC",ink:"#332D2B",muted:"#6F5C57",button:"#302C2A",buttonInk:"#FFFFFF",hair:"#836351",highlight:"#B8927D"},
 {id:"romantic",name:"Romantic soft",top:"#F6E9ED",bottom:"#E7BFC9",ink:"#5B3945",muted:"#8D5C6B",button:"#AA667F",buttonInk:"#FFFFFF",hair:"#78505B",highlight:"#C596AA"},
 {id:"brunette",name:"Luxury brunette",top:"#201B19",bottom:"#3A2924",ink:"#F7ECD9",muted:"#D7C0A7",button:"#D9BA86",buttonInk:"#231A15",hair:"#33201B",highlight:"#9B6950"},
 {id:"bold",name:"Bold color fashion",top:"#AE284E",bottom:"#48134B",ink:"#FFF4FA",muted:"#F6CDE5",button:"#FFFFFF",buttonInk:"#721546",hair:"#9E286A",highlight:"#F58CD7"},
 {id:"bridal",name:"Bridal / natural",top:"#FFF9F0",bottom:"#E9DDD1",ink:"#53443C",muted:"#7B665D",button:"#DED0BC",buttonInk:"#40342D",hair:"#957B64",highlight:"#D8BE9A"},
 {id:"cozy",name:"Cozy seasonal",top:"#D5B59E",bottom:"#765141",ink:"#FFF7ED",muted:"#F5DCCA",button:"#FFF4E6",buttonInk:"#543629",hair:"#4E302A",highlight:"#B27D60"},
 {id:"urban",name:"Urban trendy",top:"#D9D8D6",bottom:"#717078",ink:"#202127",muted:"#44464C",button:"#202127",buttonInk:"#FFFFFF",hair:"#292B31",highlight:"#A9A8B1"},
 {id:"chrome",name:"Creative party / chrome",top:"#211B38",bottom:"#66417C",ink:"#FCF4FF",muted:"#E5CBF6",button:"#D7BBEF",buttonInk:"#221932",hair:"#565577",highlight:"#CFB4F4"}
];
function drawHairBackground(ctx: CanvasRenderingContext2D, id: HairTemplateId) {
 const t=hairTemplates.find(x=>x.id===id)??hairTemplates[0];
 const g=ctx.createLinearGradient(0,0,1080,1920);g.addColorStop(0,t.top);g.addColorStop(1,t.bottom);
 ctx.fillStyle=g;ctx.fillRect(0,0,1080,1920);
 // Locally rendered editorial hair illustration; deterministic, exportable without network/CORS.
 ctx.save();ctx.translate(740,790);
 if(id==="bold")ctx.rotate(-.18);if(id==="urban")ctx.rotate(.12);
 const hair=ctx.createLinearGradient(-350,-420,380,700);
 hair.addColorStop(0,t.highlight);hair.addColorStop(.42,t.hair);hair.addColorStop(1,t.highlight);
 ctx.fillStyle=hair;
 ctx.beginPath();ctx.moveTo(-260,540);ctx.bezierCurveTo(-420,140,-390,-440,-90,-525);
 ctx.bezierCurveTo(210,-600,400,-340,335,40);ctx.bezierCurveTo(290,260,410,550,320,690);
 ctx.lineTo(-260,690);ctx.closePath();ctx.fill();
 ctx.fillStyle=id==="brunette"?"#BD8D76":"#E7BEA9";
 ctx.beginPath();ctx.ellipse(6,-90,176,255,0,0,Math.PI*2);ctx.fill();
 ctx.fillStyle=hair;ctx.beginPath();ctx.moveTo(-210,-335);ctx.bezierCurveTo(-145,-560,260,-520,254,-283);
 ctx.bezierCurveTo(150,-340,95,-440,25,-407);ctx.bezierCurveTo(-52,-335,-88,-280,-210,-270);ctx.closePath();ctx.fill();
 ctx.lineWidth=24;ctx.strokeStyle=t.highlight;ctx.globalAlpha=.5;
 for(let i=0;i<7;i++){ctx.beginPath();ctx.moveTo(-245+i*79,-212+(i%3)*21);ctx.bezierCurveTo(-355+i*63,105,-255+i*77,360,-232+i*77,650);ctx.stroke();}
 if(id==="bridal"){ctx.strokeStyle="#FFFFFF";ctx.lineWidth=12;ctx.beginPath();ctx.arc(-15,-375,205,Math.PI*1.1,Math.PI*1.9);ctx.stroke();}
 if(id==="chrome"){ctx.strokeStyle="#EBCFFE";ctx.lineWidth=21;ctx.beginPath();ctx.moveTo(-260,350);ctx.quadraticCurveTo(50,590,320,360);ctx.stroke();}
 if(id==="cozy"){ctx.fillStyle="#C9A27F";ctx.fillRect(-330,585,660,150);}
 ctx.restore();
 const wash=ctx.createLinearGradient(0,0,0,1920);
 const dark=["brunette","bold","cozy","chrome"].includes(id);
 wash.addColorStop(0,dark?"rgba(0,0,0,.22)":"rgba(255,255,255,.12)");
 wash.addColorStop(.33,"rgba(0,0,0,0)");
 wash.addColorStop(.7,dark?"rgba(0,0,0,.35)":"rgba(255,255,255,.22)");
 wash.addColorStop(1,dark?"rgba(0,0,0,.63)":"rgba(255,255,255,.65)");
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
function coverImage(ctx:CanvasRenderingContext2D,img:HTMLImageElement,x:number,y:number,w:number,h:number){
const scale=Math.max(w/img.naturalWidth,h/img.naturalHeight),sw=w/scale,sh=h/scale;
ctx.drawImage(img,(img.naturalWidth-sw)/2,(img.naturalHeight-sh)/2,sw,sh,x,y,w,h);
}
async function drawNailBackground(ctx:CanvasRenderingContext2D,template:NailTemplateId){
const t=nailTemplates.find(x=>x.id===template)??nailTemplates[0];
const gradient=ctx.createLinearGradient(0,0,0,1920);
gradient.addColorStop(0,t.top);gradient.addColorStop(1,t.bottom);
ctx.fillStyle=gradient;ctx.fillRect(0,0,1080,1920);
const image=new Image();image.crossOrigin="anonymous";
image.src="https://images.unsplash.com/"+t.photo+"?auto=format&fit=crop&w=1080&h=1920&q=85";
try{
 await new Promise<void>((resolve,reject)=>{image.onload=()=>resolve();image.onerror=()=>reject(new Error("Image not available"));});
 ctx.save();ctx.globalAlpha=t.id==="urban"?.82:.87;coverImage(ctx,image,0,0,1080,1920);ctx.restore();
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
async function drawStory(canvas: HTMLCanvasElement, master: Master, service: Service, date: string, time: string, style: Style, link: string, theme: Theme, nailTemplate: NailTemplateId, hairTemplate: HairTemplateId, browTemplate: BrowTemplateId) {
    canvas.width = WIDTH;
    canvas.height = HEIGHT;
    const ctx = canvas.getContext("2d");
    if (!ctx) throw new Error("Браузер не поддерживает создание изображений.");
    const design = nailTemplates.find(t=>t.id===nailTemplate)??nailTemplates[0];
    const hairDesign = hairTemplates.find(t=>t.id===hairTemplate)??hairTemplates[0];
    const browDesign = browTemplates.find(t=>t.id===browTemplate)??browTemplates[0];
    const p = theme==="brows" ? {bg:browDesign.top,fg:browDesign.ink,sub:browDesign.muted,action:browDesign.button,actionText:browDesign.buttonInk} : theme==="hair" ? {bg:hairDesign.top,fg:hairDesign.ink,sub:hairDesign.muted,action:hairDesign.button,actionText:hairDesign.buttonInk} : theme==="nails" ? {bg:design.top,fg:design.ink,sub:design.muted,action:design.button,actionText:design.buttonInk} : palettes[style];
    if(theme==="nails") await drawNailBackground(ctx,nailTemplate);
    else if(theme==="hair") drawHairBackground(ctx,hairTemplate);
    else if(theme==="brows") drawBrowBackground(ctx,browTemplate);
    else {ctx.fillStyle=p.bg;ctx.fillRect(0,0,WIDTH,HEIGHT);drawArt(ctx,theme);}
    const margin = 100;
    ctx.textBaseline = "top";
    ctx.fillStyle = p.fg;
    ctx.font = "bold 45px system-ui, sans-serif";
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
    ctx.font = "500 36px system-ui, sans-serif";
    const mast = wrappedText(ctx, master.display_name, 650, 2);
    mast.forEach((line, i) => ctx.fillText(line, margin, 235 + i * 48));
    ctx.fillStyle = p.sub;
    ctx.font = "500 38px system-ui, sans-serif";
    ctx.fillText("ЕСТЬ СВОБОДНОЕ ВРЕМЯ", margin, 580);
    ctx.fillStyle = p.fg;
    ctx.font = "bold 94px system-ui, sans-serif";
    wrappedText(ctx, theme==="nails" ? "Маникюр" : theme==="hair" ? "Волосы" : theme==="brows" ? "Брови" : "Освободилось окно", 870, 3).forEach((line, i) => ctx.fillText(line, margin, 690 + i * 112));
    ctx.font = "600 54px system-ui, sans-serif";
    wrappedText(ctx, service.name, 860, 2).forEach((line, i) => ctx.fillText(line, margin, 1030 + i * 68));
    const niceDate = new Date(date + "T12:00:00Z").toLocaleDateString("ru-RU", { timeZone: "UTC", day: "numeric", month: "long" });
    ctx.font = "bold 138px system-ui, sans-serif";
    ctx.fillText(time, margin, 1230);
    ctx.font = "500 49px system-ui, sans-serif";
    ctx.fillText(niceDate + " · " + service.price_som.toLocaleString("ru-RU") + " сом", margin, 1400);
    ctx.fillStyle = p.action;
    ctx.beginPath();
    ctx.roundRect(margin, 1570, WIDTH - margin * 2, 108, 54);
    ctx.fill();
    ctx.fillStyle = p.actionText;
    ctx.font = "600 48px system-ui, sans-serif";
    ctx.fillText("Записаться", margin + 55, 1594);
    ctx.fillStyle = p.sub;
    ctx.font = "500 28px system-ui, sans-serif";
    const url = new URL(link);
    const displayLink = url.host + url.pathname;
    ctx.fillText(displayLink.length > 46 ? displayLink.slice(0, 43) + "…" : displayLink, margin, 1740);
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
    const [style, setStyle] = useState<Style>(master.story_style && master.story_style in palettes ? master.story_style as Style : "minimal");
    const [error, setError] = useState("");
    const [busy, setBusy] = useState(false);
    const [prepared, setPrepared] = useState<File | null>(null);
    const canvas = useRef<HTMLCanvasElement>(null);
    const [themeChoice,setThemeChoice]=useState<Theme|null>(null);
    const [nailTemplate,setNailTemplate]=useState<NailTemplateId>(()=> master.story_template?.startsWith("bosh-nail-trends:") ? (master.story_template.split(":")[1] as NailTemplateId) : "nude");
    const [hairTemplate,setHairTemplate]=useState<HairTemplateId>(()=> master.story_template?.startsWith(HAIR_COLLECTION.slug+":") && hairTemplates.some(t=>t.id===master.story_template?.split(":")[1]) ? master.story_template.split(":")[1] as HairTemplateId : "nude");
    const [browTemplate,setBrowTemplate]=useState<BrowTemplateId>(()=>master.story_template?.startsWith(BROW_COLLECTION.slug+":") && browTemplates.some(t=>t.id===master.story_template?.split(":")[1]) ? master.story_template.split(":")[1] as BrowTemplateId : "soft-pink");
    const [savingTemplate,setSavingTemplate]=useState(false);
    const [savedTemplate,setSavedTemplate]=useState<string|null>(master.story_template??null);
    const renderSequence=useRef(0);
    const service = activeServices.find(s => s.id === serviceId);
    const theme=themeChoice ?? (service ? storyTheme(service,master) : "general");
    useEffect(()=>setThemeChoice(null),[serviceId]);
    const link = new URL(`/m/${encodeURIComponent(master.slug)}?date=${encodeURIComponent(date)}&service=${serviceId}&time=${encodeURIComponent(time)}`, window.location.origin).toString();

    useEffect(() => {
        const sequence=++renderSequence.current;
        setPrepared(null);
        setError("");
        if (service && canvas.current && /^\d{4}-\d{2}-\d{2}$/.test(date) && /^\d{2}:\d{2}$/.test(time)) {
            const preview=canvas.current;
            // A temporary canvas prevents late image loads from overwriting a newer template.
            const offscreen=document.createElement("canvas");
            void drawStory(offscreen,master,service,date,time,style,link,theme,nailTemplate,hairTemplate,browTemplate)
                .then(()=>{if(sequence!==renderSequence.current)return;const c=preview.getContext("2d");if(c){preview.width=offscreen.width;preview.height=offscreen.height;c.drawImage(offscreen,0,0);}})
                .catch(()=>{if(sequence===renderSequence.current)setError("Не удалось отобразить сторис.");});
        }
    }, [master, service, date, time, style, link, theme, nailTemplate, hairTemplate, browTemplate]);
    useEffect(() => {
        const handleKey = (event: KeyboardEvent) => { if (event.key === "Escape") onClose(); };
        window.addEventListener("keydown", handleKey);
        return () => window.removeEventListener("keydown", handleKey);
    }, [onClose]);
    const chooseStyle = (next: Style) => {
        setStyle(next);
        axios.post("/stories/style", { style: next }).catch(() => setError("Не удалось запомнить стиль. Изображение можно сохранить."));
    };

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
        if (!service || !canvas.current) return;
        setBusy(true);
        setError("");
        setPrepared(null);
        try {
            const response = await axios.get<{available: boolean; reason?: string}>("/stories/availability", {params: {service: service.id, date, time}});
            if (!response.data.available) throw new Error(response.data.reason || "Это время сейчас недоступно.");
            await drawStory(canvas.current, master, service, date, time, style, link, theme, nailTemplate, hairTemplate, browTemplate);
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
        <section className="story-dialog panel" role="dialog" aria-modal="true" aria-label="Создать сторис">
            <div className="row between"><h2>Сторис об окне</h2><CloseButton onClick={onClose} label="Закрыть" /></div>
            <div className="story-grid">
                <div className="story-fields">
                    <label>Услуга<select value={serviceId} onChange={e => setServiceId(Number(e.target.value))}>{activeServices.map(s => <option key={s.id} value={s.id}>{s.name} · {s.price_som.toLocaleString("ru-RU")} сом</option>)}</select></label>
                    <div className="story-input-row">
                        <label>Дата<input type="date" value={date} onChange={e => setDate(e.target.value)} /></label>
                        <label>Время<input type="time" step="1800" value={time} onChange={e => setTime(e.target.value)} /></label>
                    </div>
                    <label>Тематика изображения<select value={theme} onChange={e=>setThemeChoice(e.target.value as Theme)}>{(Object.keys(themes) as Theme[]).map(key=><option key={key} value={key}>{themes[key]}</option>)}</select></label>
                    <p className="muted small">Тематика подобрана по услуге автоматически. Можно изменить.</p>
                    {theme==="nails" && <div className="story-nail-templates" role="group" aria-label="Маникюрные шаблоны">
                        {nailTemplates.map(t=><button type="button" key={t.id} aria-pressed={nailTemplate===t.id} className={nailTemplate===t.id?"story-nail-template selected":"story-nail-template"} onClick={()=>{setNailTemplate(t.id);void saveTemplate("bosh-nail-trends",t.id);}}>
                            <span className="story-nail-thumb" style={{backgroundImage:"linear-gradient(180deg,transparent,rgba(0,0,0,.45)),url(https://images.unsplash.com/"+t.photo+"?auto=format&fit=crop&w=160&h=260&q=55)"}}/>
                            <span>{t.name}</span>
                        </button>)}
                    </div>}
                    {theme==="hair" && <div>
                        <p className="muted small">{HAIR_COLLECTION.name}</p>
                        <div className="story-nail-templates" role="group" aria-label={HAIR_COLLECTION.name}>
                            {hairTemplates.map(t=><button type="button" key={t.id} aria-pressed={hairTemplate===t.id} className={hairTemplate===t.id?"story-nail-template selected":"story-nail-template"} disabled={savingTemplate} onClick={()=>{setHairTemplate(t.id);void saveTemplate(HAIR_COLLECTION.slug,t.id);}}>
                                <span className="story-nail-thumb story-hair-thumb" style={{background:`linear-gradient(145deg,${t.top},${t.bottom})`}}><span className="story-hair-strand" style={{background:t.hair,boxShadow:`inset -9px -2px 0 ${t.highlight}`}}/></span>
                                <span>{t.name}</span>
                            </button>)}
                        </div>
                        <p className="muted small" role="status">{savingTemplate?"Сохраняем шаблон…":savedTemplate===HAIR_COLLECTION.slug+":"+hairTemplate?"Шаблон сохранён":""}</p>
                    </div>}
                    {theme==="brows" && <div>
                        <p className="muted small">{BROW_COLLECTION.name} · 8 тематических оформлений</p>
                        <div className="story-nail-templates" role="group" aria-label={BROW_COLLECTION.name}>
                            {browTemplates.map(t=><button type="button" key={t.id} aria-pressed={browTemplate===t.id} className={browTemplate===t.id?"story-nail-template selected":"story-nail-template"} disabled={savingTemplate} onClick={()=>{setBrowTemplate(t.id);void saveTemplate(BROW_COLLECTION.slug,t.id);}}>
                                <span className="story-nail-thumb story-brow-thumb" style={{background:`linear-gradient(145deg,${t.top},${t.bottom})`,color:t.ink}}><span className="story-brow-arch" style={{borderColor:t.brows}}/><span className="story-brow-eye" style={{borderColor:t.brows}}/></span>
                                <span>{t.name}</span>
                            </button>)}
                        </div>
                        <p className="muted small" role="status">{savingTemplate?"Сохраняем шаблон…":savedTemplate===BROW_COLLECTION.slug+":"+browTemplate?"Шаблон сохранён":""}</p>
                    </div>}
                    {theme!=="nails" && theme!=="hair" && theme!=="brows" && <div className="story-palettes" role="group" aria-label="Стиль сторис">
                        {(Object.entries(palettes) as [Style, typeof palettes[Style]][]).map(([key, p]) =>
                            <button type="button" key={key} className={style === key ? "story-palette selected" : "story-palette"} aria-pressed={style === key} onClick={() => chooseStyle(key)}>
                                <span className="story-color" style={{background: p.bg, color: p.fg}}>Aa</span><span>{p.name}</span>
                            </button>)}
                    </div>}
                    <p className="muted small">Сторис создаётся в размере 1080 × 1920. Ссылка на запись подготовлена отдельно для стикера Instagram.</p>
                    <div className="row wrap">
                        <button type="button" className="primary" disabled={busy || !service} onClick={() => void prepare()}>{busy ? "Проверяем время…" : "Создать сторис"}</button>
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
