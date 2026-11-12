type PasswordHintsProps = {
    password: string;
    confirmation: string;
};

export default function PasswordHints({ password, confirmation }: PasswordHintsProps) {
    const lengthValid = password.length >= 6 && password.length <= 128;
    const charsValid = /^[!-~]*$/.test(password);
    const matches = confirmation.length > 0 && password === confirmation;

    const rules = [
        { label: "От 6 до 128 символов", valid: lengthValid, touched: password.length > 0 },
        { label: "Латиница, цифры или знаки, без пробелов", valid: charsValid, touched: password.length > 0 },
        { label: "Пароли совпадают", valid: matches, touched: confirmation.length > 0 },
    ];

    return (
        <div className="password-hints" aria-label="Требования к паролю" aria-live="polite" aria-atomic="true">
            {rules.map(rule => (
                <div key={rule.label} className={`password-hint ${!rule.touched ? "password-hint--idle" : rule.valid ? "password-hint--valid" : "password-hint--invalid"}`}>
                    <span aria-hidden="true" className="password-hint-indicator">
                        {!rule.touched ? "–" : rule.valid ? "✓" : "×"}
                    </span>
                    <span>{rule.label}</span>
                </div>
            ))}
        </div>
    );
}
