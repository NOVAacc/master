import PhoneInput from "./PhoneInput";
import { useForm } from "@inertiajs/react";
import { useEffect, useState } from "react";
import { Errors, Field } from "./Shared";
import ProfileIdentity from "./ProfileIdentity";
import type { Master } from "./types";

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
    const [uploadKey, setUploadKey] = useState({ avatar: 0, cover: 0 });
    const f = useForm({
        slug: master.slug,
        display_name: master.display_name,
        specialty: master.specialty || "",
        bio: master.bio || "",
        instagram_username: master.instagram_username || "",
        profile_theme: master.profile_theme || "graphite",
        phone: master.phone || "",
        address: master.address || "",
        timezone: master.timezone,
        booking_enabled: master.booking_enabled,
        auto_confirm: master.auto_confirm,
        whatsapp_notifications: master.whatsapp_notifications || false,
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
            f.setError(kind, "Выберите JPG, PNG или WebP размером до 2 МБ.");
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
                        },
                    });
                }}
            >
                <Errors errors={f.errors} />
                <section className="profile-settings-section">
                    <p className="eyebrow">ВИЗУАЛЬНЫЙ СТИЛЬ</p>
                    <h3>Фото и обложка</h3>
                    <p className="muted small">
                        JPG, PNG или WebP, до 2 МБ. Аватар лучше квадратный,
                        обложка — горизонтальная.
                    </p>
                    {(
                        [
                            [
                                "avatar",
                                "Аватар",
                                "Рекомендуем 400 × 400 пикселей",
                            ],
                            [
                                "cover",
                                "Обложка профиля",
                                "Рекомендуем 1600 × 600 пикселей",
                            ],
                        ] as const
                    ).map(([kind, label, hint]) => (
                        <div className="profile-upload" key={kind}>
                            <Field label={label}>
                                <input
                                    key={`${kind}-${uploadKey[kind]}`}
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    onChange={(e) =>
                                        selectFile(
                                            kind,
                                            e.target.files?.[0] || null,
                                        )
                                    }
                                    disabled={f.processing}
                                />
                                <small>{hint}</small>
                            </Field>
                            {(kind === "avatar" ? avatar : cover) && (
                                <button
                                    type="button"
                                    className="text-button"
                                    disabled={f.processing}
                                    onClick={() => {
                                        f.setData((d) => ({
                                            ...d,
                                            [kind]: null,
                                            [`remove_${kind}`]: true,
                                        }));
                                        setUploadKey((k) => ({
                                            ...k,
                                            [kind]: k[kind] + 1,
                                        }));
                                    }}
                                >
                                    Убрать изображение
                                </button>
                            )}
                        </div>
                    ))}
                    <fieldset className="profile-theme-options">
                        <legend className="field-title">
                            Цвет шапки без обложки
                        </legend>
                        {(
                            [
                                ["graphite", "Графит"],
                                ["sand", "Песочный"],
                                ["sage", "Шалфей"],
                                ["lilac", "Лаванда"],
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
                                {name}
                            </label>
                        ))}
                    </fieldset>
                </section>
                <section className="profile-settings-section">
                    <p className="eyebrow">О ВАС</p>
                    <h3>Первое впечатление</h3>
                    <Field label="Имя на странице">
                        <input
                            required
                            maxLength={100}
                            value={f.data.display_name}
                            onChange={(e) =>
                                f.setData("display_name", e.target.value)
                            }
                        />
                    </Field>
                    <Field label="Адрес вашей страницы">
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
                                aria-label="Уникальный адрес публичной страницы"
                            />
                        </div>
                        <small>От 3 до 30 символов: только латинские буквы и цифры. Адрес должен быть свободен.</small>
                        {f.data.slug !== master.slug && (
                            <small>После смены адреса прежняя ссылка перестанет работать. Поделитесь новой ссылкой с клиентами.</small>
                        )}
                        {/^[a-z0-9]{3,30}$/.test(f.data.slug) && (
                            <small>Новый адрес: {typeof window !== "undefined" ? window.location.origin : "https://bosh.kg"}/m/{f.data.slug}</small>
                        )}
                    </Field>
                    <Field label="Специализация">
                        <input
                            maxLength={150}
                            placeholder="Мастер по наращиванию ресниц"
                            value={f.data.specialty}
                            onChange={(e) =>
                                f.setData("specialty", e.target.value)
                            }
                        />
                    </Field>
                    <Field label="О себе">
                        <textarea
                            maxLength={2000}
                            value={f.data.bio}
                            onChange={(e) => f.setData("bio", e.target.value)}
                        />
                    </Field>
                </section>
                <section className="profile-settings-section">
                    <p className="eyebrow">КОНТАКТЫ</p>
                    <h3>Как вас найти</h3>
                    <Field label="Instagram — ник или ссылка">
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
                            Клиент сможет открыть ваш Instagram одним нажатием.
                        </small>
                    </Field>
                    <Field label="Телефон / WhatsApp">
                        <PhoneInput value={f.data.phone} onChange={value => f.setData("phone", value)} label="Телефон мастера" />
                    </Field>
                    <Field label="Адрес приёма">
                        <input
                            maxLength={255}
                            value={f.data.address}
                            onChange={(e) =>
                                f.setData("address", e.target.value)
                            }
                        />
                    </Field>
                </section>
                <details className="profile-advanced">
                    <summary>Дополнительные настройки записи</summary>
                    <Field label="Часовой пояс">
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
                                <option key={zone}>{zone}</option>
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
                        Принимать онлайн-записи
                    </label>
                    <label className="check">
                        <input
                            type="checkbox"
                            checked={f.data.auto_confirm}
                            onChange={(e) =>
                                f.setData("auto_confirm", e.target.checked)
                            }
                        />
                        Подтверждать записи автоматически
                    </label>
                    <label className="check">
                        <input
                            type="checkbox"
                            checked={f.data.whatsapp_notifications}
                            onChange={(e) =>
                                f.setData(
                                    "whatsapp_notifications",
                                    e.target.checked,
                                )
                            }
                        />
                        Получать подтверждённые записи в WhatsApp
                    </label>
                    <p className="muted small">
                        Автоматические уведомления начнут работать после
                        подключения отправки. Для связи с клиентом доступна
                        кнопка WhatsApp в записи.
                    </p>
                </details>
                <div className="profile-save-bar">
                    <button className="primary" disabled={f.processing}>
                        {f.processing ? "Сохраняем…" : "Сохранить профиль"}
                    </button>
                    {f.progress && (
                        <small role="status">
                            Загрузка: {f.progress.percentage}%
                        </small>
                    )}
                    {f.recentlySuccessful && (
                        <small role="status">Профиль сохранён</small>
                    )}
                </div>
            </form>
            <aside className="profile-settings-preview">
                <p className="eyebrow">ПРЕДПРОСМОТР</p>
                <div className="profile-preview-card">
                    <ProfileIdentity master={preview} />
                    {f.data.bio && (
                        <p className="profile-preview-bio">{f.data.bio}</p>
                    )}
                </div>
                <p className="muted small">
                    Так будет выглядеть ваша страница после сохранения.
                </p>
            </aside>
        </div>
    );
}
