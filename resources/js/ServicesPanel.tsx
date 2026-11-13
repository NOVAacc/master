import { useI18n } from "./i18n";
import CloseButton from "./CloseButton";
import Icon from "./Icon";
import { useEffect, useRef, useState } from "react";
import { useForm } from "@inertiajs/react";
import { Errors, Field } from "./Shared";
import type { Service } from "./types";
import { serviceBookingLink } from "./service-links";

function ServiceEditor({
    service,
    onClose,
}: {
    service?: Service;
    onClose: () => void;
}) {
    const { t: tr, locale } = useI18n();
    const dialog = useRef<HTMLDialogElement>(null);
    useEffect(() => {
        const element = dialog.current;
        element?.showModal();
        return () => element?.close();
    }, []);
    const f = useForm({
        name: service?.name ?? "",
        description: service?.description ?? "",
        duration_minutes: service?.duration_minutes ?? 60,
        price_som: service?.price_som ?? 0,
        is_active: service?.is_active ?? true,
    });
    const requestClose = () => {
        if (f.processing) return;
        if (f.isDirty && !confirm(tr("Закрыть форму без сохранения изменений?")))
            return;
        onClose();
    };
    return (
        <dialog
            ref={dialog}
            className="service-dialog"
            aria-labelledby="service-dialog-title"
            onCancel={(e) => {
                e.preventDefault();
                requestClose();
            }}
        >
            <div className="service-dialog-header">
                <h2 id="service-dialog-title">
                    {service ? tr("Редактирование услуги") : tr("Новая услуга")}
                </h2>
                <CloseButton label={tr("Закрыть форму услуги")} disabled={f.processing} onClick={requestClose} />
            </div>
            <form
                className="service-editor"
                onSubmit={(e) => {
                    e.preventDefault();
                    f.post(service ? `/services/${service.id}` : "/services", {
                        preserveScroll: true,
                        onSuccess: onClose,
                    });
                }}
            >
                <Errors errors={f.errors} />
                <div className="form-grid">
                    <Field label={tr("Название")}>
                        <input
                            autoFocus
                            required
                            maxLength={150}
                            value={f.data.name}
                            onChange={(e) => f.setData("name", e.target.value)}
                        />
                    </Field>
                    <Field label={tr("Описание")}>
                        <textarea
                            maxLength={2000}
                            value={f.data.description}
                            onChange={(e) =>
                                f.setData("description", e.target.value)
                            }
                        />
                    </Field>
                    <Field label={tr("Длительность, минут")}>
                        <input
                            required
                            type="number"
                            min={5}
                            max={720}
                            value={f.data.duration_minutes}
                            onChange={(e) =>
                                f.setData(
                                    "duration_minutes",
                                    Number(e.target.value),
                                )
                            }
                        />
                    </Field>
                    <Field label={tr("Цена, сом")}>
                        <input
                            required
                            type="number"
                            min={0}
                            max={10000000}
                            value={f.data.price_som}
                            onChange={(e) =>
                                f.setData("price_som", Number(e.target.value))
                            }
                        />
                    </Field>
                </div>
                <div className="row between">
                    <label className="check">
                        <input
                            type="checkbox"
                            checked={f.data.is_active}
                            onChange={(e) =>
                                f.setData("is_active", e.target.checked)
                            }
                        />
                        {tr("Доступна для записи")}{" "}</label>
                    <div className="row wrap">
                        <button
                            type="button"
                            className="ghost"
                            disabled={f.processing}
                            onClick={requestClose}
                        >
                            {tr("Отмена")}{" "}</button>
                        <button className="primary" disabled={f.processing}>
                            {f.processing
                                ? tr("Сохраняем…")
                                : service
                                  ? tr("Сохранить")
                                  : tr("Добавить услугу")}
                        </button>
                    </div>
                </div>
            </form>
        </dialog>
    );
}

export default function ServicesPanel({ services, publicUrl }: { services: Service[]; publicUrl: string }) {
    const { t: tr, locale } = useI18n();
    const [query, setQuery] = useState("");
    const [linkSelections, setLinkSelections] = useState<number[]>([]);
    const [linkFeedback, setLinkFeedback] = useState("");
    async function copyBookingLink(ids: number[]): Promise<void> {
        const availableIds = new Set(services.filter(item => item.is_active).map(item => item.id));
        if (!ids.length || ids.some(id => !availableIds.has(id))) {
            setLinkFeedback(tr("Выберите доступные для записи услуги."));
            return;
        }
        const link = serviceBookingLink(publicUrl, ids);
        try {
            await navigator.clipboard.writeText(link);
            setLinkFeedback(tr("Ссылка скопирована."));
        } catch {
            setLinkFeedback(tr("Не удалось скопировать автоматически. Скопируйте ссылку из поля ниже."));
        }
    }
    const [filter, setFilter] = useState("all");
    const [sort, setSort] = useState("default");
    const [page, setPage] = useState(1);
    const [editor, setEditor] = useState<number | "new" | null>(null);
    const term = query.trim().toLocaleLowerCase((locale === "ky" ? "ky-KG" : "ru-RU"));
    const matched = services.filter(
        (service) =>
            (!term ||
                `${service.name} ${service.description || ""}`
                    .toLocaleLowerCase((locale === "ky" ? "ky-KG" : "ru-RU"))
                    .includes(term)) &&
            (filter === "all" ||
                (filter === "active" ? service.is_active : !service.is_active)),
    );
    if (sort === "name")
        matched.sort((a, b) => a.name.localeCompare(b.name, (locale === "ky" ? "ky-KG" : "ru-RU")));
    if (sort === "price")
        matched.sort(
            (a, b) =>
                a.price_som - b.price_som ||
                a.name.localeCompare(b.name, (locale === "ky" ? "ky-KG" : "ru-RU")),
        );
    const pages = Math.max(1, Math.ceil(matched.length / 10));
    const current = Math.min(page, pages);
    const shown = matched.slice((current - 1) * 10, current * 10);
    const selected =
        typeof editor === "number"
            ? services.find((service) => service.id === editor)
            : undefined;
    return (
        <div className="services-catalog">
            <div className="row between wrap services-summary">
                <p className="muted small">
                    {tr("Всего:")}{" "}{services.length} {tr("· Доступно для записи:")}{" "}
                    {services.filter((s) => s.is_active).length}
                </p>
                <button
                    type="button"
                    className="primary"
                    onClick={() => setEditor("new")}
                >
                    {tr("+ Добавить услугу")}{" "}</button>
            </div>
            <div className="panel" aria-label={tr("Ссылки для записи")}>
                <h3>{tr("Ссылки на услуги")}</h3>
                <p className="muted small">
                    {tr("Отправьте клиенту ссылку на одну услугу или готовый набор. Выбранные услуги откроются сразу, без повторного поиска.")}{" "}</p>
                <div className="row wrap">
                    <button className="ghost" type="button"
                        disabled={linkSelections.length === 0}
                        onClick={() => void copyBookingLink(linkSelections)}>
                        {tr("Скопировать ссылку на набор (")}{linkSelections.length})
                    </button>
                    <button className="ghost" type="button" disabled={!linkSelections.length}
                        onClick={() => {setLinkSelections([]); setLinkFeedback("");}}>
                        {tr("Очистить выбор")}{" "}</button>
                </div>
                {linkSelections.length > 0 && (
                    <input aria-label={tr("Ссылка на выбранные услуги")} type="text" readOnly
                        value={serviceBookingLink(publicUrl, linkSelections)}
                        onFocus={event => event.currentTarget.select()} />
                )}
                {linkFeedback && <p role="status" className="muted small">{linkFeedback}</p>}
            </div>
            <div className="services-tools">
                <input
                    type="search"
                    aria-label={tr("Поиск услуг")}
                    placeholder={tr("Поиск по названию или описанию")}
                    value={query}
                    onChange={(e) => {
                        setQuery(e.target.value);
                        setPage(1);
                    }}
                />
                <select
                    aria-label={tr("Активность услуг")}
                    value={filter}
                    onChange={(e) => {
                        setFilter(e.target.value);
                        setPage(1);
                    }}
                >
                    <option value="all">{tr("Все услуги")}</option>
                    <option value="active">{tr("Доступные")}</option>
                    <option value="hidden">{tr("Скрытые")}</option>
                </select>
                <select
                    aria-label={tr("Порядок услуг")}
                    value={sort}
                    onChange={(e) => {
                        setSort(e.target.value);
                        setPage(1);
                    }}
                >
                    <option value="default">{tr("По порядку")}</option>
                    <option value="name">{tr("По названию")}</option>
                    <option value="price">{tr("По цене ↑")}</option>
                </select>
            </div>
            {shown.length ? (
                <div className={`services-table-scroll${shown.length > 12 ? " is-long" : ""}`}>
                    <table className="services-table">
                        <thead>
                            <tr>
                                <th scope="col" className="service-select-cell"><span className="sr-only">{tr("Выбрать для ссылки")}</span></th>
                                <th scope="col">{tr("Услуга")}</th>
                                <th scope="col">{tr("Время")}</th>
                                <th scope="col">{tr("Стоимость")}</th>
                                <th scope="col">{tr("Статус")}</th>
                                <th scope="col">
                                    <span className="sr-only">{tr("Действия")}</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {shown.map((service) => (
                                <tr key={service.id}>
                                    <td className="service-select-cell">
                                        <input type="checkbox" disabled={!service.is_active}
                                            aria-label={tr("Включить «{0}» в ссылку", [service.name])}
                                            checked={linkSelections.includes(service.id)}
                                            onChange={event => {
                                                setLinkFeedback("");
                                                setLinkSelections(current => event.target.checked
                                                    ? [...current, service.id]
                                                    : current.filter(id => id !== service.id));
                                            }}/>
                                    </td>
                                    <td className="service-name-cell">
                                        <strong>{service.name}</strong>
                                        {service.description && (
                                            <p title={service.description}>
                                                {service.description}
                                            </p>
                                        )}
                                    </td>
                                    <td className="service-duration-cell">
                                        {service.duration_minutes} {tr("мин")}{" "}</td>
                                    <td className="service-price-cell">
                                        {service.price_som.toLocaleString(
                                            (locale === "ky" ? "ky-KG" : "ru-RU"),
                                        )}{" "}
                                        {tr("сом")}{" "}</td>
                                    <td className="service-status-cell">
                                        <span
                                            className={`badge${service.is_active ? " confirmed" : ""}`}
                                        >
                                            {service.is_active
                                                ? tr("Доступна")
                                                : tr("Скрыта")}
                                        </span>
                                    </td>
                                    <td className="service-action-cell">
                                        <div className="service-action-buttons">
                                            {service.is_active && (
                                                <button className="service-icon-button" type="button"
                                                    title={tr("Скопировать ссылку")}
                                                    aria-label={tr("Скопировать ссылку на услугу «{0}»", [service.name])}
                                                    onClick={() => void copyBookingLink([service.id])}>
                                                    <Icon name="link" />
                                                </button>
                                            )}
                                            <button type="button" className="service-icon-button"
                                                title={tr("Изменить услугу")}
                                                aria-label={tr("Изменить услугу «{0}»", [service.name])}
                                                onClick={() => setEditor(service.id)}>
                                                <Icon name="pencil" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : (
                <div className="empty">
                    <h3>
                        {services.length
                            ? tr("Услуги не найдены")
                            : tr("Добавьте первую услугу")}
                    </h3>
                    <p>
                        {services.length
                            ? tr("Измените поисковый запрос или фильтр.")
                            : tr("Укажите название, цену и длительность — услуга появится на странице записи.")}
                    </p>
                    {services.length > 0 && (
                        <button
                            type="button"
                            className="ghost"
                            onClick={() => {
                                setQuery("");
                                setFilter("all");
                                setPage(1);
                            }}
                        >
                            {tr("Сбросить поиск")}{" "}</button>
                    )}
                </div>
            )}
            <div className="services-pagination">
                <p className="muted small" role="status">
                    {matched.length
                        ? tr("{0}–{1} из {2}", [(current - 1) * 10 + 1, Math.min(current * 10, matched.length), matched.length])
                        : tr("0 услуг")}
                </p>
                <nav className="row" aria-label={tr("Страницы услуг")}>
                    <button
                        type="button"
                        className="ghost"
                        disabled={current === 1}
                        onClick={() => setPage(current - 1)}
                    >
                        {tr("Назад")}{" "}</button>
                    <span className="muted small">
                        {current} / {pages}
                    </span>
                    <button
                        type="button"
                        className="ghost"
                        disabled={current === pages}
                        onClick={() => setPage(current + 1)}
                    >
                        {tr("Далее")}{" "}</button>
                </nav>
            </div>
            {editor !== null && (editor === "new" || selected) && (
                <ServiceEditor
                    key={editor}
                    service={selected}
                    onClose={() => setEditor(null)}
                />
            )}
        </div>
    );
}
