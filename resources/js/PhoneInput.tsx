import type { ChangeEvent } from "react";

// The Kyrgyz country code is immutable in the interface. All forms submit
// the same canonical 12-digit number without a "+" sign.
export function normalizeKyrgyzPhone(value: string | null | undefined): string {
    const digits = (value || "").replace(/\D/g, "");
    if (!digits) return "";
    if (digits.startsWith("996")) return "996" + digits.slice(3, 12);
    if (digits.startsWith("0") && digits.length === 10) return "996" + digits.slice(1);
    return "996" + digits.slice(0, 9);
}

export default function PhoneInput({
    value,
    onChange,
    required = false,
    label = "Телефон",
    autoComplete = "tel",
}: {
    value: string | null | undefined;
    onChange: (value: string) => void;
    required?: boolean;
    label?: string;
    autoComplete?: string;
}) {
    const digits = normalizeKyrgyzPhone(value).slice(3);
    const handleChange = (event: ChangeEvent<HTMLInputElement>) => {
        const local = event.target.value.replace(/\D/g, "").slice(0, 9);
        onChange(local ? "996" + local : "");
    };
    return (
        <div className="phone-number-field">
            <span className="phone-country-prefix" aria-hidden="true">996</span>
            <input
                type="tel"
                inputMode="numeric"
                autoComplete={autoComplete}
                aria-label={label + " — после 996 введите девять цифр"}
                placeholder="555 123 456"
                value={digits}
                maxLength={9}
                pattern="[0-9]{9}"
                required={required}
                onChange={handleChange}
            />
        </div>
    );
}
