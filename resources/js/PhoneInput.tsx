import type { ChangeEvent } from "react";

export default function PhoneInput({
    value,
    onChange,
    required = false,
    label = "Телефон",
    autoComplete = "tel",
    fixedPrefix = false,
    showHint = true,
}: {
    value: string | null | undefined;
    onChange: (value: string) => void;
    required?: boolean;
    label?: string;
    autoComplete?: string;
    fixedPrefix?: boolean;
    showHint?: boolean;
}) {
    const digits = (value || "").replace(/\D/g, "");
    const local = digits.startsWith("996") ? digits.slice(3, 12) : digits.slice(0, 9);
    const handleChange = (event: ChangeEvent<HTMLInputElement>) => {
        const typed = event.target.value.replace(/\D/g, "");
        if (fixedPrefix) {
            const number = typed.startsWith("996") && typed.length > 9
                ? typed.slice(3, 12)
                : typed.slice(0, 9);
            onChange(number ? "996" + number : "");
        } else {
            onChange(typed.slice(0, 15));
        }
    };

    return (
        <div className={fixedPrefix ? "phone-number-field phone-fixed-prefix" : "phone-number-field"}>
            {fixedPrefix && <span className="phone-country-prefix" aria-hidden="true">996</span>}
            <input
                type="tel"
                inputMode="numeric"
                autoComplete={autoComplete}
                aria-label={!showHint ? label : fixedPrefix ? label + ": 9 цифр после 996, без плюса" : label + ": только цифры, без плюса"}
                placeholder={fixedPrefix ? "555123456" : "996555123456"}
                value={fixedPrefix ? local : digits}
                maxLength={fixedPrefix ? 9 : 15}
                pattern={fixedPrefix ? "[0-9]{9}" : "[0-9]{7,15}"}
                required={required}
                onChange={handleChange}
            />
            {showHint && !fixedPrefix && <small className="phone-hint">Только цифры, без «+»</small>}
        </div>
    );
}
