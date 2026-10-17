import { useEffect, useState } from "react";

export type ProfileIdentityData = {
    display_name: string;
    specialty?: string | null;
    tagline?: string | null;
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
                {master.tagline && (
                    <p className="profile-tagline">{master.tagline}</p>
                )}
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
            </div>
        </div>
    );
}
