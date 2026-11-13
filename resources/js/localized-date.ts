import type { Locale } from './i18n';

const weekdays: Record<string, string> = {
    'понедельник': 'дүйшөмбү', 'вторник': 'шейшемби', 'среда': 'шаршемби',
    'четверг': 'бейшемби', 'пятница': 'жума', 'суббота': 'ишемби', 'воскресенье': 'жекшемби',
    'пн': 'дш', 'вт': 'шш', 'ср': 'шр', 'чт': 'бш', 'пт': 'жм', 'сб': 'иш', 'вс': 'жш',
};
const months: Record<string, string> = {
    'январь': 'январь', 'января': 'январь', 'февраль': 'февраль', 'февраля': 'февраль',
    'март': 'март', 'марта': 'март', 'апрель': 'апрель', 'апреля': 'апрель',
    'май': 'май', 'мая': 'май', 'июнь': 'июнь', 'июня': 'июнь', 'июль': 'июль', 'июля': 'июль',
    'август': 'август', 'августа': 'август', 'сентябрь': 'сентябрь', 'сентября': 'сентябрь',
    'октябрь': 'октябрь', 'октября': 'октябрь', 'ноябрь': 'ноябрь', 'ноября': 'ноябрь',
    'декабрь': 'декабрь', 'декабря': 'декабрь',
};
/** Use widely supported Russian ICU data, then replace date words explicitly for Kyrgyz. */
export function localizedDateTimeFormat(locale: Locale, options: Intl.DateTimeFormatOptions) {
    const formatter = new Intl.DateTimeFormat('ru-RU', options);
    return {
        format(value: Date | number): string {
            if (locale === 'ru') return formatter.format(value);
            return formatter.formatToParts(value).map(part => {
                if (part.type === 'weekday') return weekdays[part.value.toLowerCase()] ?? part.value;
                if (part.type === 'month') return months[part.value.toLowerCase()] ?? part.value;
                if (part.type === 'literal') return part.value.replace(/\s*г\./g, '-ж.');
                return part.value;
            }).join('');
        },
    };
}
