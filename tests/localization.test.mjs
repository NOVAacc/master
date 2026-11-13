import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import ts from 'typescript';

const dictionary = JSON.parse(readFileSync(new URL('../resources/js/locales/ky.json', import.meta.url), 'utf8'));
const files = ["Pages/Home.tsx", "Pages/Auth.tsx", "Pages/PasswordRecovery.tsx", "PasswordHints.tsx", "PhoneInput.tsx", "Shared.tsx", "DisplayMode.tsx", "Pages/Dashboard.tsx", "Pages/Booking.tsx", "Pages/ClientDetails.tsx", "Pages/ManageBooking.tsx", "AccountSecurity.tsx", "DeleteAccount.tsx", "AppointmentCalendar.tsx", "BookingCalendar.tsx", "ClientCard.tsx", "CloseButton.tsx", "EditAppointmentForm.tsx", "FeedbackDrawer.tsx", "FirstIndustryChoice.tsx", "MasterModeSwitch.tsx", "ProfileIdentity.tsx", "ProfileSettings.tsx", "QuickBooking.tsx", "ServicesPanel.tsx", "ShareProfile.tsx", "StoryCreator.tsx"];
test('Kyrgyz dictionary covers all source phrases in the localized public and master interfaces', () => {
    const missing = new Set();
    for (const file of files) {
        const content = readFileSync(new URL('../resources/js/' + file, import.meta.url), 'utf8');
        const source = ts.createSourceFile(file, content, ts.ScriptTarget.Latest, true, ts.ScriptKind.TSX);
        const visit = node => {
            // Story matching keywords are internal search rules, not UI copy.
            if (ts.isVariableDeclaration(node) && ['rules', 'norm'].includes(node.name.getText(source))) return;
            if (ts.isJsxAttribute(node) && node.name.getText(source) === 'value') {
                assert.ok(!/\btr\(/.test(node.initializer?.getText(source) ?? ''), 'Do not translate editable form values: ' + file);
            }
            if (ts.isStringLiteral(node) && /[А-Яа-яЁё]/.test(node.text) && !dictionary[node.text]) missing.add(node.text);
            if (ts.isJsxText(node) && /[А-Яа-яЁё]/.test(node.text)) missing.add('Untranslated JSX: ' + node.text.trim());
            ts.forEachChild(node, visit);
        };
        visit(source);
    }
    assert.deepEqual([...missing], []);
    for (const [phrase, translated] of Object.entries(dictionary)) {
        assert.ok(phrase.trim() && typeof translated === 'string' && translated.trim(), phrase);
    }
});

test('Kyrgyz dictionary covers system industry and category labels', () => {
    for (const file of ['config/industries.php', 'app/Support/CategoryStoryCatalog.php']) {
        const source = readFileSync(new URL('../' + file, import.meta.url), 'utf8');
        const labels = [...source.matchAll(/=>\s*'([^'\n]*[А-Яа-яЁё][^'\n]*)'/g)].map(match => match[1]);
        for (const label of labels) assert.ok(dictionary[label], file + ': ' + label);
    }
});

test('Kyrgyz calendar dates never fall back to English and preserve the master timezone', async () => {
    const { localizedDateTimeFormat } = await import('../resources/js/localized-date.ts');
    const instant = new Date('2026-11-13T20:30:00Z');
    const options = { timeZone: 'Asia/Bishkek', weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
    const kyrgyz = localizedDateTimeFormat('ky', options).format(instant);
    assert.match(kyrgyz, /ишемби/);
    assert.match(kyrgyz, /14 ноябрь 2026-ж\./);
    assert.doesNotMatch(kyrgyz, /November|Saturday/);
    assert.equal(localizedDateTimeFormat('ru', options).format(instant), new Intl.DateTimeFormat('ru-RU', options).format(instant));
});
