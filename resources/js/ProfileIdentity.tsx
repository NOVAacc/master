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
}: {
    master: ProfileIdentityData;
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
            </div>
            <div className="profile-identity-content">
                <MasterAvatar master={master} large />
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
