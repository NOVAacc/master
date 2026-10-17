import test from 'node:test';
import assert from 'node:assert/strict';
import { addDays, weekStart, localDay, intervalOnDay, arrangeIntervals } from '../resources/js/calendar.ts';

test('calendar dates cross months and years without browser timezone offsets', () => {
    assert.equal(weekStart('2026-01-01'), '2025-12-29');
    assert.equal(addDays('2026-01-01', -1), '2025-12-31');
    assert.equal(addDays('2028-02-28', 1), '2028-02-29');
    assert.equal(localDay('2026-10-18T20:00:00Z', 'Asia/Bishkek'), '2026-10-19');
});
test('booking geometry uses master local time and the full appointment duration', () => {
    assert.deepEqual(intervalOnDay('2026-10-19T04:00:00Z', '2026-10-19T06:30:00Z', '2026-10-19', 'Asia/Bishkek'), { start: 600, end: 750 });
    assert.equal(intervalOnDay('2026-10-19T04:00:00Z', '2026-10-19T06:30:00Z', '2026-10-20', 'Asia/Bishkek'), null);
    assert.deepEqual(intervalOnDay('2026-10-18T17:30:00Z', '2026-10-18T18:30:00Z', '2026-10-19', 'Asia/Bishkek'), { start: 0, end: 30 });
    assert.equal(intervalOnDay('2026-10-18T17:00:00Z', '2026-10-18T18:00:00Z', '2026-10-19', 'Asia/Bishkek'), null);
});
test('overlapping cancelled and active appointments stay visible beside each other', () => {
    const items = arrangeIntervals([{ id: 'long', start: 600, end: 780 }, { id: 'overlap', start: 630, end: 700 }, { id: 'adjacent', start: 780, end: 840 }]);
    assert.equal(items[0].lanes, 2);
    assert.equal(items[1].lanes, 2);
    assert.notEqual(items[0].lane, items[1].lane);
    assert.equal(items[2].lanes, 1);
});
test('chain overlaps use consistent lanes and adjacent appointments reuse a lane', () => {
    const items = arrangeIntervals([{ id: 'a', start: 0, end: 60 }, { id: 'b', start: 30, end: 90 }, { id: 'c', start: 60, end: 120 }]);
    assert.equal(items[0].lane, items[2].lane);
    assert.ok(items.every(item => item.lanes === 2));
    assert.deepEqual(arrangeIntervals([]), []);
});
