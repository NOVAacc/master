import { Head, Link, useForm } from "@inertiajs/react";
import { Brand, Errors, Field } from "../Shared";
export default function Auth({ register }: { register: boolean }) {
    const f = useForm({
        name: "",
        email: "",
        password: "",
        password_confirmation: "",
        slug: "",
    });
    return (
        <main className="auth-wrap">
            <section className="auth-intro">
                <Brand />
                <h1>Время для вашего дела.</h1>
                <p>Услуги, расписание и запись клиентов — в одном кабинете.</p>
                <div className="intro-card">
                    <span>Всё по плану</span>
                    <h2>
                        Ваш следующий клиент
                        <br />
                        уже выбирает время.
                    </h2>
                    <p>Создайте страницу и поделитесь личной ссылкой.</p>
                </div>
            </section>
            <section className="auth-form panel">
                <Head title={register ? "Регистрация" : "Вход"} />
                <p className="eyebrow">КАБИНЕТ СПЕЦИАЛИСТА</p>
                <h1>{register ? "Начнём с знакомства" : "С возвращением"}</h1>
                <p className="muted">
                    {register
                        ? "Создайте профиль своего дела."
                        : "Войдите, чтобы управлять записями."}
                </p>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        f.post(register ? "/register" : "/login", {
                            onFinish: () =>
                                f.reset("password", "password_confirmation"),
                        });
                    }}
                >
                    <Errors errors={f.errors} />
                    {register && (
                        <>
                            <Field label="Ваше имя">
                                <input
                                    required
                                    maxLength={100}
                                    value={f.data.name}
                                    onChange={(e) =>
                                        f.setData("name", e.target.value)
                                    }
                                    autoComplete="name"
                                />
                            </Field>
                            <Field label="Адрес страницы">
                                <div className="input-prefix">
                                    <span>/m/</span>
                                    <input
                                        required
                                        pattern="[a-z][a-z0-9-]{2,59}"
                                        placeholder="aizada"
                                        value={f.data.slug}
                                        onChange={(e) =>
                                            f.setData(
                                                "slug",
                                                e.target.value.toLowerCase(),
                                            )
                                        }
                                    />
                                </div>
                                <small>
                                    3–60 символов: латинские буквы, цифры и
                                    дефис.
                                </small>
                            </Field>
                        </>
                    )}
                    <Field label="Email">
                        <input
                            required
                            type="email"
                            value={f.data.email}
                            onChange={(e) => f.setData("email", e.target.value)}
                            autoComplete="username"
                        />
                    </Field>
                    <Field label="Пароль">
                        <input
                            required
                            minLength={register ? 10 : undefined}
                            type="password"
                            value={f.data.password}
                            onChange={(e) =>
                                f.setData("password", e.target.value)
                            }
                            autoComplete={
                                register ? "new-password" : "current-password"
                            }
                        />
                    </Field>
                    {register && (
                        <Field label="Повторите пароль">
                            <input
                                required
                                type="password"
                                value={f.data.password_confirmation}
                                onChange={(e) =>
                                    f.setData(
                                        "password_confirmation",
                                        e.target.value,
                                    )
                                }
                                autoComplete="new-password"
                            />
                        </Field>
                    )}
                    <button className="primary wide" disabled={f.processing}>
                        {f.processing
                            ? "Подождите…"
                            : register
                              ? "Создать кабинет"
                              : "Войти"}
                    </button>
                </form>
                <p className="muted">
                    {register ? "Уже зарегистрированы?" : "Первый раз здесь?"}{" "}
                    <Link href={register ? "/login" : "/register"}>
                        {register ? "Войти" : "Создать кабинет"}
                    </Link>
                </p>
            </section>
        </main>
    );
}
