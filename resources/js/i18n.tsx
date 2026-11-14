import kyrgyzFlag from "../../public/brand/kyrgyzstan-flag.svg?url";
import { createContext, useContext, useEffect, useState, type ReactNode } from 'react';
import kyrgyz from './locales/ky.json';
export type Locale = 'ru' | 'ky';
const dictionary: Record<string, string> = kyrgyz;
const storageKey = 'bosh.locale';
const LocaleContext = createContext<{ locale: Locale; setLocale: (locale: Locale) => void }>({ locale: 'ru', setLocale: () => {} });
export type Translator = (text: string, values?: readonly unknown[]) => string;
export function translate(text: string, locale: Locale, values: readonly unknown[] = []): string {
    const translated = locale === 'ky' ? dictionary[text] ?? text : text;
    return translated.replace(/\{(\d+)\}/g, (placeholder, index: string) => Number(index) < values.length ? String(values[Number(index)]) : placeholder);
}
function savedLocale(): Locale {
    try { return localStorage.getItem(storageKey) === 'ky' ? 'ky' : 'ru'; } catch { return 'ru'; }
}
export function LocaleProvider({ children }: { children: ReactNode }) {
    const [locale, setLocale] = useState<Locale>(savedLocale);
    useEffect(() => {
        document.documentElement.lang = locale;
        try { localStorage.setItem(storageKey, locale); } catch { /* Keep working without persistent storage. */ }
    }, [locale]);
    useEffect(() => {
        const sync = (event: StorageEvent) => { if (event.key === storageKey || event.key === null) setLocale(savedLocale()); };
        window.addEventListener('storage', sync);
        return () => window.removeEventListener('storage', sync);
    }, []);
    return <LocaleContext.Provider value={{ locale, setLocale }}>{children}</LocaleContext.Provider>;
}
export function useI18n() {
    const context = useContext(LocaleContext);
    return { ...context, t: (text: string, values?: readonly unknown[]) => translate(text, context.locale, values) };
}
export default function LanguageSwitch({ mobileFooter = false }: { mobileFooter?: boolean }) {
    const { locale, setLocale } = useI18n();
    return <div className={`language-switch${mobileFooter ? " mobile-footer-language" : ""}`} role="group" aria-label={locale === 'ky' ? 'Тилди тандоо' : 'Выбор языка'}>
        <button type="button" lang="ru" aria-label="Русский" aria-pressed={locale === 'ru'} title="Русский" onClick={() => setLocale('ru')}><span className="language-code">RU</span><svg className="language-flag" viewBox="0 0 30 20" aria-hidden="true"><path fill="#fff" d="M0 0h30v20H0z"/><path fill="#0039a6" d="M0 6.67h30v6.66H0z"/><path fill="#d52b1e" d="M0 13.33h30V20H0z"/></svg></button>
        <button type="button" lang="ky" aria-label="Кыргызча" aria-pressed={locale === 'ky'} title="Кыргызча" onClick={() => setLocale('ky')}><span className="language-code">KY</span><img className="language-flag" src={kyrgyzFlag} alt="" aria-hidden="true" /></button>
    </div>;
}
