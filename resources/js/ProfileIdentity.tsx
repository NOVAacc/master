import { useEffect, useState } from "react";

export type ProfileIdentityData = {
    display_name: string;
    specialty?: string | null;
    phone?: string | null;
    avatar_url?: string | null;
    cover_url?: string | null;
    instagram_username?: string | null;
    instagram_url?: string | null;
    profile_theme?: string | null;
};
export function MasterAvatar({
    master,
    large = false,
}: {
    master: ProfileIdentityData;
    large?: boolean;
}) {
    const [failed, setFailed] = useState(false);
    useEffect(() => setFailed(false), [master.avatar_url]);
    return (
        <div className={`avatar master-avatar${large ? " large" : ""}`}>
            <span aria-hidden="true">
                {Array.from(master.display_name || "М")[0]}
            </span>
            {master.avatar_url && !failed && (
                <img
                    src={master.avatar_url}
                    alt={`Фото: ${master.display_name}`}
                    onError={() => setFailed(true)}
                />
            )}
        </div>
    );
}
export default function ProfileIdentity({
    master,
    imageEditing,
}: {
    master: ProfileIdentityData;
    imageEditing?: {
        disabled?: boolean;
        editAvatar: () => void;
        editCover: () => void;
        removeAvatar: () => void;
        removeCover: () => void;
    };
}) {
    const phone = (master.phone || "").replace(/\D/g, "");
    const whatsappUrl = /^[1-9][0-9]{6,14}$/.test(phone)
        ? `https://wa.me/${phone}`
        : null;
    return (
        <div
            className={`profile-identity theme-${master.profile_theme || "graphite"}`}
        >
            <div className="profile-cover">
                {master.cover_url && (
                    <img src={master.cover_url} alt="Обложка профиля" />
                )}
                {imageEditing && <div className="profile-cover-edit-actions">
                    <button type="button" className="profile-image-edit-button" disabled={imageEditing.disabled} onClick={imageEditing.editCover} aria-label="Загрузить или заменить обложку">Изменить обложку</button>
                    {master.cover_url && <button type="button" className="profile-image-remove-button" disabled={imageEditing.disabled} onClick={imageEditing.removeCover} aria-label="Удалить обложку">Убрать</button>}
                </div>}
            </div>
            <div className="profile-identity-content">
                {imageEditing ? <div className="profile-avatar-edit-wrap">
                    <MasterAvatar master={master} large />
                    <button type="button" className="profile-avatar-edit-button" disabled={imageEditing.disabled} onClick={imageEditing.editAvatar} aria-label="Загрузить или заменить аватар" title="Изменить аватар">✎</button>
                    {master.avatar_url && <button type="button" className="profile-avatar-remove-button" disabled={imageEditing.disabled} onClick={imageEditing.removeAvatar} aria-label="Удалить аватар" title="Удалить аватар">×</button>}
                </div> : <MasterAvatar master={master} large />}
                <h1>{master.display_name || "Ваше имя"}</h1>
                <p className="profile-specialty">
                    {master.specialty || "Специализация"}
                </p>
                <div className="profile-social-links">
                {master.instagram_url && master.instagram_username && (
                    <a
                        className="instagram-link"
                        href={master.instagram_url}
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <svg
                            width="16"
                            height="16"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            strokeWidth="1.7"
                            aria-hidden="true"
                        >
                            <rect x="3" y="3" width="18" height="18" rx="5" />
                            <circle cx="12" cy="12" r="4" />
                            <circle
                                cx="17.5"
                                cy="6.5"
                                r=".8"
                                fill="currentColor"
                                stroke="none"
                            />
                        </svg>
                        @{master.instagram_username}
                        <span aria-hidden="true">↗</span>
                    </a>
                )}
                {whatsappUrl && (
                    <a
                        className="instagram-link"
                        href={whatsappUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label={`Написать мастеру в WhatsApp: ${master.phone}`}
                        title={master.phone || undefined}
                    >
                        <svg width="16" height="16" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" strokeWidth="1.7" aria-hidden="true">
                            <path d="M21 11.5a9 9 0 0 1-13.4 7.9L3 21l1.6-4.6A9 9 0 1 1 21 11.5Z" />
                            <path d="M8 7.5c0 4.5 4 8 8 8l1-2-2.5-1-1 1a7 7 0 0 1-3-3l1-1-1-2.5Z" />
                        </svg>
                        WhatsApp
                        <span aria-hidden="true">↗</span>
                    </a>
                )}
                </div>
            </div>
        </div>
    );
}
