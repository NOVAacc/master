export function localDay(value: string | Date, timezone: string): string {
    const parts = new Intl.DateTimeFormat("en-CA", {
        timeZone: timezone,
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
    }).formatToParts(new Date(value));
    return ["year", "month", "day"]
        .map((key) => parts.find((p) => p.type === key)!.value)
        .join("-");
}
export function addDays(day: string, amount: number): string {
    const date = new Date(`${day}T12:00:00Z`);
    date.setUTCDate(date.getUTCDate() + amount);
    return date.toISOString().slice(0, 10);
}
export function weekStart(day: string): string {
    const weekday = new Date(`${day}T12:00:00Z`).getUTCDay() || 7;
    return addDays(day, 1 - weekday);
}
export function clockMinutes(value: string): number {
    const [h, m] = value.split(":").map(Number);
    return h * 60 + m;
}
function localMinutes(value: string, timezone: string): number {
    const parts = new Intl.DateTimeFormat("en-GB", {
        timeZone: timezone,
        hour: "2-digit",
        minute: "2-digit",
        hourCycle: "h23",
    }).formatToParts(new Date(value));
    return (
        Number(parts.find((p) => p.type === "hour")!.value) * 60 +
        Number(parts.find((p) => p.type === "minute")!.value)
    );
}
export function intervalOnDay(
    start: string,
    end: string,
    day: string,
    timezone: string,
): { start: number; end: number } | null {
    const first = localDay(start, timezone),
        last = localDay(end, timezone);
    if (first > day || last < day) return null;
    const from = first < day ? 0 : localMinutes(start, timezone);
    const to = last > day ? 1440 : localMinutes(end, timezone);
    return to > from ? { start: from, end: to } : null;
}
export type CalendarInterval = { id: string; start: number; end: number };
export function arrangeIntervals<T extends CalendarInterval>(
    items: T[],
): (T & { lane: number; lanes: number })[] {
    const result: (T & { lane: number; lanes: number })[] = [];
    let cluster: (T & { lane: number; lanes: number })[] = [];
    let ends: number[] = [];
    let clusterEnd = -1;
    function finish() {
        for (const item of cluster) item.lanes = ends.length;
        cluster = [];
        ends = [];
    }
    for (const item of [...items].sort(
        (a, b) => a.start - b.start || a.end - b.end,
    )) {
        if (item.start >= clusterEnd) finish();
        let lane = ends.findIndex((end) => end <= item.start);
        if (lane === -1) lane = ends.length;
        ends[lane] = item.end;
        clusterEnd = Math.max(cluster.length ? clusterEnd : -1, item.end);
        const placed = { ...item, lane, lanes: 1 };
        cluster.push(placed);
        result.push(placed);
    }
    finish();
    return result;
}
