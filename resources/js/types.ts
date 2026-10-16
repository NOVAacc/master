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
    bio: string;
    phone: string;
    address: string;
    timezone: string;
    booking_enabled: boolean;
    auto_confirm: boolean;
};
export type Hour = { weekday: number; starts_at: string; ends_at: string };
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
};
