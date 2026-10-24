import { drawStory } from "./StoryCreator";
import type { Master, Service } from "./types";

type Config = {
  theme: Parameters<typeof drawStory>[7];
  template: string;
  photo_url: string;
  default_photo_url: string;
  image_url: string | null;
  image_position: "top" | "center" | "bottom";
  image_horizontal: "left" | "center" | "right";
  image_zoom: number;
  font: "system" | "serif" | "rounded" | "elegant" | "classic" | "bold" | "mono";
  scale: number;
  shade: number;
  text_color: string;
};
const input = document.getElementById("story-preview-data");
const canvas = document.getElementById("storyPreview") as HTMLCanvasElement | null;
const form = document.getElementById("templateForm") as HTMLFormElement | null;
const message = document.getElementById("previewMessage");
if (input && canvas && form) {
  const initial = JSON.parse(input.textContent || "{}") as Config;
  const image = form.elements.namedItem("image") as HTMLInputElement;
  const reset = form.elements.namedItem("remove_image") as HTMLInputElement;
  const font = form.elements.namedItem("font") as HTMLSelectElement;
  const scale = form.elements.namedItem("scale") as HTMLSelectElement;
  const shade = form.elements.namedItem("shade") as HTMLSelectElement;
  const color = form.elements.namedItem("text_color") as HTMLInputElement;
  const zoom = form.elements.namedItem("image_zoom") as HTMLSelectElement;
  const ctx = canvas.getContext("2d");
  let objectUrl: string | null = null;
  let revision = 0;
  let frame = 0;
  const setStatus = (value: string) => { if (message) message.textContent = value; };
  const release = () => { if (objectUrl) URL.revokeObjectURL(objectUrl); objectUrl = null; };
  const master = { display_name: "Мастер BOSH" } as Master;
  const service = { name: "Пример услуги", price_som: 1500 } as Service;
  const draw = async () => {
    const current = ++revision;
    const photo = reset.checked ? initial.default_photo_url : objectUrl || initial.image_url || initial.photo_url;
    const config = {
      photo_url: photo,
      image_url: photo,
      image_position: initial.image_position,
      image_horizontal: initial.image_horizontal,
      image_zoom: Number(zoom.value),
      font: font.value,
      scale: Number(scale.value),
      shade: Number(shade.value),
      text_color: color.value,
    };
    const catalog = { [initial.theme]: { [initial.template]: config } } as Parameters<typeof drawStory>[12];
    const temp = document.createElement("canvas");
    setStatus("Обновление предпросмотра…");
    try {
      await drawStory(temp, master, service, "2026-10-25", "16:30", "minimal",
        "https://bosh.kg/m/primer", initial.theme,
        (initial.theme === "nails" ? initial.template : "nude") as Parameters<typeof drawStory>[8],
        (initial.theme === "hair" ? initial.template : "nude") as Parameters<typeof drawStory>[9],
        (initial.theme === "brows" ? initial.template : "nude") as Parameters<typeof drawStory>[10],
        initial.template, catalog);
      if (current !== revision) return;
      if (!ctx) throw new Error("Canvas недоступен");
      canvas.width = temp.width;
      canvas.height = temp.height;
      ctx.drawImage(temp, 0, 0);
      setStatus("Предпросмотр соответствует генератору PNG.");
    } catch {
      if (current === revision) setStatus("Не удалось создать предпросмотр. Проверьте изображение.");
    }
  };
  const queue = () => { cancelAnimationFrame(frame); frame = requestAnimationFrame(() => { void draw(); }); };
  image.addEventListener("change", () => {
    release();
    const file = image.files?.[0];
    if (file) {
      if (!["image/jpeg", "image/png", "image/webp"].includes(file.type) || file.size > 4 * 1024 * 1024) {
        setStatus("Файл должен быть JPG, PNG или WebP размером до 4 МБ.");
        image.value = "";
      } else {
        reset.checked = false;
        objectUrl = URL.createObjectURL(file);
      }
    }
    queue();
  });
  reset.addEventListener("change", () => {
    if (reset.checked) { release(); image.value = ""; }
    queue();
  });
  for (const element of [font, scale, shade, color, zoom]) element.addEventListener("input", queue);
  window.addEventListener("pagehide", () => { ++revision; cancelAnimationFrame(frame); release(); }, { once: true });
  queue();
}
