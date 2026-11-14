import { useI18n } from "./i18n";
import { timezoneLabel } from "./timezone";
import PhoneInput from "./PhoneInput";
import { router, useForm, usePage } from "@inertiajs/react";
import { useEffect, useRef, useState } from "react";
import { Errors, Field } from "./Shared";
import ProfileIdentity from "./ProfileIdentity";
import type { Master, Industries } from "./types";

function useImagePreview(
    file: File | null,
    saved: string | null | undefined,
    remove: boolean,
) {
    const [preview, setPreview] = useState<string | null>(null);
    useEffect(() => {
        if (!file) {
            setPreview(null);
            return;
        }
        const url = URL.createObjectURL(file);
        setPreview(url);
        return () => URL.revokeObjectURL(url);
    }, [file]);
    return remove ? null : file ? preview : saved;
}
function instagram(value: string) {
    let name = value.trim().replace(/^@/, "");
    if (/instagram\.com\//i.test(name)) {
        try {
            const url = new URL(
                /^https?:\/\//i.test(name) ? name : `https://${name}`,
            );
            if (
                !["instagram.com", "www.instagram.com"].includes(
                    url.hostname.toLowerCase(),
                )
            )
                return null;
            name = url.pathname.replace(/^\/+|\/+$/g, "");
        } catch {
            return null;
        }
    }
    return /^(?!\.)(?!.*\.\.)(?!.*\.$)[A-Za-z0-9_.]{1,30}$/.test(name)
        ? name
        : null;
}
export default function ProfileSettings({ master }: { master: Master }) {
    const { t: tr, locale } = useI18n();
    const { industries } = usePage<{ industries: Industries }>().props;
    const [uploadKey, setUploadKey] = useState({ avatar: 0, cover: 0 });
    const avatarInput = useRef<HTMLInputElement>(null);
    const coverInput = useRef<HTMLInputElement>(null);
    const f = useForm({
        slug: master.slug,
        display_name: master.display_name,
        specialty: master.specialty || "",
        industry: master.industry || "",
        activity: master.activity || "",
        bio: master.bio || "",
        instagram_username: master.instagram_username || "",
        profile_theme: master.profile_theme || "graphite",
        phone: master.phone || "",
        address: master.address || "",
        address_url: master.address_url || "",
        timezone: master.timezone,
        booking_enabled: master.booking_enabled,
        show_busy_slots: master.show_busy_slots ?? false,
        auto_confirm: master.auto_confirm,
        avatar: null as File | null,
        cover: null as File | null,
        remove_avatar: false,
        remove_cover: false,
    });
    const avatar = useImagePreview(
        f.data.avatar,
        master.avatar_url,
        f.data.remove_avatar,
    );
    const cover = useImagePreview(
        f.data.cover,
        master.cover_url,
        f.data.remove_cover,
    );
    const handle = instagram(f.data.instagram_username);
    const preview = {
        ...master,
        ...f.data,
        avatar_url: avatar,
        cover_url: cover,
        instagram_username: handle,
        instagram_url: handle
            ? `https://www.instagram.com/${encodeURIComponent(handle)}/`
            : null,
    };
    function selectFile(kind: "avatar" | "cover", file: File | null) {
        if (
            file &&
            (!["image/jpeg", "image/png", "image/webp"].includes(file.type) ||
                file.size > 2 * 1024 * 1024)
        ) {
            f.setError(kind, tr("Выберите JPG, PNG или WebP размером до 2 МБ."));
            f.setData((d) => ({ ...d, [kind]: null }));
            setUploadKey((k) => ({ ...k, [kind]: k[kind] + 1 }));
            return;
        }
        f.clearErrors(kind);
        f.setData((d) => ({ ...d, [kind]: file, [`remove_${kind}`]: false }));
    }
    return (
        <div className="profile-settings-layout">
            <form
                className="profile-settings-form"
                onSubmit={(e) => {
                    e.preventDefault();
                    f.post("/profile", {
                        forceFormData: true,
                        preserveScroll: true,
                        onSuccess: () => {
                            const next = {
                                ...f.data,
                                avatar: null,
                                cover: null,
                                remove_avatar: false,
                                remove_cover: false,
                            };
                            f.setData(next);
                            f.setDefaults(next);
                            setUploadKey((k) => ({
                                avatar: k.avatar + 1,
                                cover: k.cover + 1,
                            }));
                            // Refresh the authoritative profile after the server commits the change.
                            // This also refreshes the dashboard title, public URL and preview.
                            router.reload({ only: ["master", "publicUrl"] });
                        },
                    });
                }}
            >
                <Errors errors={f.errors} />
                <section className="profile-settings-section">
                    <p className="eyebrow">{tr("ВИЗУАЛЬНЫЙ СТИЛЬ")}</p>
                    <h3>{tr("Фото и обложка")}</h3>
                    <p className="muted small">
                        {tr("JPG, PNG или WebP, до 2 МБ. Аватар лучше квадратный, обложка — горизонтальная.")}{" "}</p>
                    <p className="muted small">{tr("Нажмите на карандаш внутри аватара или «Изменить обложку» в предпросмотре. Затем сохраните профиль.")}</p>
                    <fieldset className="profile-theme-options">
                        <legend className="field-title">
                            {tr("Цвет шапки без обложки")}{" "}</legend>
                        {(
                            [
                                ["graphite", tr("Графит")],
                                ["sand", tr("Песочный")],
                                ["sage", tr("Шалфей")],
                                ["lilac", tr("Лаванда")],
                            ] as const
                        ).map(([value, name]) => (
                            <label
                                className={`theme-option theme-${value}`}
                                key={value}
                            >
                                <input
                                    type="radio"
                                    name="profile_theme"
                                    value={value}
                                    checked={f.data.profile_theme === value}
                                    onChange={() =>
                                        f.setData("profile_theme", value)
                                    }
                                />
                                <span className="theme-swatch" />
                                {tr(name)}
                            </label>
                        ))}
                    </fieldset>
                </section>
                <section className="profile-settings-section">
                    <p className="eyebrow">{tr("О ВАС")}</p>
                    <h3>{tr("Первое впечатление")}</h3>
                    <Field label={tr("Имя на странице")}>
                        <input
                            required
                            maxLength={100}
                            value={f.data.display_name}
                            onChange={(e) =>
                                f.setData("display_name", e.target.value)
                            }
                        />
                    </Field>
                    <Field label={tr("Адрес вашей страницы")}>
                        <div className="input-prefix">
                            <span>/m/</span>
                            <input
                                required
                                minLength={3}
                                maxLength={30}
                                pattern={f.data.slug === master.slug ? undefined : "[a-z0-9]{3,30}"}
                                autoCapitalize="none"
                                autoCorrect="off"
                                spellCheck={false}
                                value={f.data.slug}
                                onChange={(e) =>
                                    f.setData("slug", e.target.value.toLowerCase())
                                }
                                aria-label={tr("Уникальный адрес публичной страницы")}
                            />
                        </div>
                        <small>{tr("От 3 до 30 символов: только латинские буквы и цифры. Адрес должен быть свободен.")}</small>
                        {f.data.slug !== master.slug && (
                            <small>{tr("После смены адреса прежняя ссылка перестанет работать. Поделитесь новой ссылкой с клиентами.")}</small>
                        )}
                        {/^[a-z0-9]{3,30}$/.test(f.data.slug) && (
                            <small>{tr("Новый адрес:")}{" "}{typeof window !== "undefined" ? window.location.origin : "https://bosh.kg"}/m/{f.data.slug}</small>
                        )}
                    </Field>
                    <div className="form-grid">
                        <Field label={tr("Сфера деятельности")}>
                            <select value={f.data.industry} onChange={e => f.setData(d => ({ ...d, industry: e.target.value, activity: "" }))}>
                                <option value="">{tr("Выберите сферу")}</option>
                                {Object.entries(industries).map(([key, item]) => <option key={key} value={key}>{tr(item.name)}</option>)}
                            </select>
                        </Field>
                        <Field label={tr("Направление")}>
                            <select value={f.data.activity} disabled={!f.data.industry} onChange={e => f.setData("activity", e.target.value)}>
                                <option value="">{tr("Выберите направление")}</option>
                                {Object.entries(industries[f.data.industry]?.activities || {}).map(([key, name]) => <option key={key} value={key}>{tr(name)}</option>)}
                            </select>
                        </Field>
                    </div>
                    <Field label={tr("Специализация")}>
                        <input
                            maxLength={150}
                            placeholder={tr("Мастер по наращиванию ресниц")}
                            value={f.data.specialty}
                            onChange={(e) =>
                                f.setData("specialty", e.target.value)
                            }
                        />
                    </Field>
                    <Field label={tr("О себе")}>
                        <textarea
                            maxLength={2000}
                            value={f.data.bio}
                            onChange={(e) => f.setData("bio", e.target.value)}
                        />
                    </Field>
                </section>
                <section className="profile-settings-section">
                    <p className="eyebrow">{tr("КОНТАКТЫ")}</p>
                    <h3>{tr("Как вас найти")}</h3>
                    <Field label={tr("Instagram — ник или ссылка")}>
                        <input
                            maxLength={250}
                            autoCapitalize="none"
                            autoCorrect="off"
                            spellCheck={false}
                            placeholder="@aizada.lashes"
                            value={f.data.instagram_username}
                            onChange={(e) =>
                                f.setData("instagram_username", e.target.value)
                            }
                        />
                        <small>
                            {tr("Клиент сможет открыть ваш Instagram одним нажатием.")}{" "}</small>
                    </Field>
                    <Field label={tr("Телефон")}>
                        <PhoneInput value={f.data.phone} onChange={value => f.setData("phone", value)} label={tr("Телефон мастера")} />
                    </Field>
                    <Field label={tr("Адрес приёма")}>
                        <input
                            maxLength={255}
                            value={f.data.address}
                            onChange={(e) =>
                                f.setData("address", e.target.value)
                            }
                        />
                    </Field>
                    <Field label={tr("Ссылка на адрес в 2ГИС")}>
                        <input
                            type="url"
                            maxLength={2048}
                            placeholder="https://2gis.kg/…"
                            autoCapitalize="none"
                            autoCorrect="off"
                            spellCheck={false}
                            value={f.data.address_url}
                            onChange={(e) => f.setData("address_url", e.target.value)}
                        />
                        <small>{tr("Вставьте ссылку из 2ГИС — клиент сможет открыть карту нажатием на адрес.")}</small>
                    </Field>
                </section>
                <details className="profile-advanced">
                    <summary>{tr("Дополнительные настройки записи")}</summary>
                    <Field label={tr("Часовой пояс")}>
                        <select
                            value={f.data.timezone}
                            onChange={(e) =>
                                f.setData("timezone", e.target.value)
                            }
                        >
                            {[
                                ...new Set([
                                    master.timezone,
                                    "Asia/Bishkek",
                                    "Asia/Almaty",
                                    "Asia/Tashkent",
                                    "Europe/Moscow",
                                    "UTC",
                                ]),
                            ].map((zone) => (
                                <option key={zone} value={zone}>{timezoneLabel(zone)}</option>
                            ))}
                        </select>
                    </Field>
                    <label className="check">
                        <input
                            type="checkbox"
                            checked={f.data.booking_enabled}
                            onChange={(e) =>
                                f.setData("booking_enabled", e.target.checked)
                            }
                        />
                        {tr("Принимать онлайн-записи")}{" "}</label>
                    <label className="check">
                        <input type="checkbox" checked={f.data.show_busy_slots}
                            onChange={(e) => f.setData("show_busy_slots", e.target.checked)} />
                        {tr("Показывать занятое время зачёркнутым")}{" "}</label>
                    <p className="muted small">{tr("Занятые часы будут видны клиенту, но выбрать их нельзя. Если выключить — показывается только свободное время.")}</p>
                    <label className="check">
                        <input
                            type="checkbox"
                            checked={f.data.auto_confirm}
                            onChange={(e) =>
                                f.setData("auto_confirm", e.target.checked)
                            }
                        />
                        {tr("Подтверждать записи автоматически")}{" "}</label>
                </details>
                <div className="profile-save-bar">
                    <button className="primary" disabled={f.processing}>
                        {f.processing ? tr("Сохраняем…") : tr("Сохранить профиль")}
                    </button>
                    {f.progress && (
                        <small role="status">
                            {tr("Загрузка:")}{" "}{f.progress.percentage}%
                        </small>
                    )}
                    {f.recentlySuccessful && (
                        <small role="status">{tr("Профиль сохранён")}</small>
                    )}
                </div>
            </form>
            <aside className="profile-settings-preview">
                <input key={`avatar-${uploadKey.avatar}`} ref={avatarInput} className="profile-image-hidden-input" type="file"
                    accept="image/jpeg,image/png,image/webp" aria-label={tr("Выбрать аватар")}
                    disabled={f.processing} onChange={e => selectFile("avatar", e.target.files?.[0] || null)} />
                <input key={`cover-${uploadKey.cover}`} ref={coverInput} className="profile-image-hidden-input" type="file"
                    accept="image/jpeg,image/png,image/webp" aria-label={tr("Выбрать обложку")}
                    disabled={f.processing} onChange={e => selectFile("cover", e.target.files?.[0] || null)} />
                <p className="eyebrow">{tr("ПРЕДПРОСМОТР")}</p>
                <div className="profile-preview-card">
                    <ProfileIdentity master={preview} imageEditing={{
                        disabled: f.processing,
                        editAvatar: () => avatarInput.current?.click(),
                        editCover: () => coverInput.current?.click(),
                        removeAvatar: () => {
                            f.setData(d => ({ ...d, avatar: null, remove_avatar: true }));
                            setUploadKey(k => ({ ...k, avatar: k.avatar + 1 }));
                        },
                        removeCover: () => {
                            f.setData(d => ({ ...d, cover: null, remove_cover: true }));
                            setUploadKey(k => ({ ...k, cover: k.cover + 1 }));
                        },
                    }} />
                    {f.data.bio && (
                        <p className="profile-preview-bio">{f.data.bio}</p>
                    )}
                </div>
                <p className="muted small">
                    {tr("Так будет выглядеть ваша страница после сохранения.")}{" "}</p>
            </aside>
        </div>
    );
}
