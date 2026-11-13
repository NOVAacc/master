import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import ts from 'typescript';

const dictionary = JSON.parse(readFileSync(new URL('../resources/js/locales/ky.json', import.meta.url), 'utf8'));
const files = ['Pages/Home.tsx', 'Pages/Auth.tsx', 'Pages/PasswordRecovery.tsx', 'PasswordHints.tsx', 'PhoneInput.tsx', 'Shared.tsx', 'DisplayMode.tsx'];
test('Kyrgyz dictionary covers all source phrases in the first localization stage', () => {
    const missing = new Set();
    for (const file of files) {
        const content = readFileSync(new URL('../resources/js/' + file, import.meta.url), 'utf8');
        const source = ts.createSourceFile(file, content, ts.ScriptTarget.Latest, true, ts.ScriptKind.TSX);
        const visit = node => {
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
