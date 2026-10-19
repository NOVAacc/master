export type Service = {
    id: number;
    name: string;
    description: string;
    duration_minutes: number;
    price_som: number;
    is_active: boolean;
};
export type Master = {
    id: number;
    slug: string;
    display_name: string;
    specialty: string;
    industry?: string | null;
    activity?: string | null;
    bio: string;
    phone: string;
    address: string;
    address_url?: string | null;
    timezone: string;
    booking_enabled: boolean;
    is_demo?: boolean;
    auto_confirm: boolean;
    whatsapp_notifications: boolean;
    avatar_url?: string | null;
    cover_url?: string | null;
    work1_url?: string | null;
    work2_url?: string | null;
    work3_url?: string | null;
    instagram_username?: string | null;
    instagram_url?: string | null;
    profile_theme?: string | null;
};
export type Hour = { weekday: number; starts_at: string; ends_at: string; break_starts_at: string | null; break_ends_at: string | null };
export type Appointment = {
    id: number;
    client_name: string;
    client_phone: string;
    client_comment: string;
    starts_at: string;
    ends_at: string;
    price_som: number;
    service_name_snapshot: string;
    services_snapshot?:
        | {
              id: number;
              name: string;
              duration_minutes: number;
              price_som: number;
          }[]
        | null;
    status: string;
    revision: number;
    notification_revision: number;
    master_service_id?: number | null;
    changes?: {
        id: number;
        created_at: string;
        reason: string | null;
        before: AppointmentSnapshot;
        after: AppointmentSnapshot;
    }[];
    whatsapp_link?: string | null;
    notifications?: {
        id: number;
        recipient: string;
        status: string;
        error_code?: string | null;
    }[];
};

export type AppointmentSnapshot = {
    starts_at: string;
    ends_at: string;
    duration_minutes: number;
    price_som: number;
    service_name: string;
    client_name: string;
    client_phone: string;
    client_comment: string | null;
};

export type Industries = Record<string, { name: string; activities: Record<string, string> }>;
