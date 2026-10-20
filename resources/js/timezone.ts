/** Human-readable UTC offset; IANA zone identifiers remain unchanged for calculations. */
export function timezoneLabel(timezone: string, date: Date | string = new Date()): string {
    const offset = new Intl.DateTimeFormat("en-US", {
        timeZone: timezone,
        timeZoneName: "shortOffset",
    }).formatToParts(new Date(date)).find(part => part.type === "timeZoneName")?.value ?? "GMT";
    return offset === "GMT" ? "UTC +0" : offset.replace("GMT", "UTC ").replace("−", "-");
}
