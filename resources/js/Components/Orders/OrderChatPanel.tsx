import type { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { FormEvent, useCallback, useEffect, useMemo, useRef, useState } from 'react';

type ChatMessage = {
    id: number;
    body: string;
    sender_role: string | null;
    sender_role_label?: string | null;
    actor_context?: string | null;
    on_behalf_label?: string | null;
    sender?: { id: number; name: string; display_name?: string } | null;
    actor?: { id: number; name: string; display_name?: string } | null;
    created_at: string | null;
    type?: string | null;
};

type ChatMeta = {
    has_more_before: boolean;
    has_more_after: boolean;
    oldest_id: number | null;
    newest_id: number | null;
    limit: number;
    can_reply_chat: boolean;
};

type Props = {
    indexUrl: string;
    storeUrl: string;
    canReplyChat: boolean;
};

function formatTime(value: string | null, locale: string): string {
    if (!value) {
        return '';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat(locale === 'ar' ? 'ar' : 'en', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
}

export default function OrderChatPanel({ indexUrl, storeUrl, canReplyChat }: Props) {
    const page = usePage<PageProps>().props;
    const t = page.t?.orders ?? {};
    const locale = page.locale;
    const isRtl = page.direction === 'rtl';
    const align = isRtl ? 'right' : 'left';

    const [messages, setMessages] = useState<ChatMessage[]>([]);
    const [meta, setMeta] = useState<ChatMeta | null>(null);
    const [body, setBody] = useState('');
    const [loading, setLoading] = useState(true);
    const [loadingOlder, setLoadingOlder] = useState(false);
    const [sending, setSending] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const bottomRef = useRef<HTMLDivElement | null>(null);

    const canReply = canReplyChat && (meta?.can_reply_chat ?? canReplyChat);

    const mergeUnique = useCallback((current: ChatMessage[], incoming: ChatMessage[]) => {
        const map = new Map<number, ChatMessage>();
        [...current, ...incoming].forEach((m) => map.set(m.id, m));
        return Array.from(map.values()).sort((a, b) => a.id - b.id);
    }, []);

    const loadInitial = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const { data } = await axios.get(indexUrl, { params: { limit: 30 } });
            setMessages(data.data ?? []);
            setMeta(data.meta ?? null);
            requestAnimationFrame(() => bottomRef.current?.scrollIntoView({ behavior: 'auto' }));
        } catch {
            setError(t.chat_locked ?? 'Unable to load chat.');
        } finally {
            setLoading(false);
        }
    }, [indexUrl, t.chat_locked]);

    useEffect(() => {
        void loadInitial();
    }, [loadInitial]);

    const loadOlder = async () => {
        if (!meta?.has_more_before || !meta.oldest_id || loadingOlder) {
            return;
        }

        setLoadingOlder(true);
        setError(null);
        try {
            const { data } = await axios.get(indexUrl, {
                params: { before_id: meta.oldest_id, limit: 30 },
            });
            setMessages((prev) => mergeUnique(prev, data.data ?? []));
            setMeta((prev) => ({
                ...(data.meta ?? prev),
                can_reply_chat: data.meta?.can_reply_chat ?? prev?.can_reply_chat ?? canReplyChat,
            }));
        } catch {
            setError(t.chat_locked ?? 'Unable to load chat.');
        } finally {
            setLoadingOlder(false);
        }
    };

    const send = async (e: FormEvent) => {
        e.preventDefault();
        const trimmed = body.trim();
        if (!trimmed || sending || !canReply) {
            return;
        }

        setSending(true);
        setError(null);
        try {
            const { data } = await axios.post(storeUrl, { body: trimmed });
            const created = data.message as ChatMessage;
            setMessages((prev) => mergeUnique(prev, [created]));
            setBody('');
            setMeta((prev) =>
                prev
                    ? {
                          ...prev,
                          newest_id: created.id,
                          oldest_id: prev.oldest_id ?? created.id,
                      }
                    : prev,
            );
            requestAnimationFrame(() => bottomRef.current?.scrollIntoView({ behavior: 'smooth' }));
        } catch (err: any) {
            const message =
                err?.response?.data?.errors?.body?.[0]
                ?? err?.response?.data?.message
                ?? (t.chat_locked ?? 'Unable to send message.');
            setError(String(message));
        } finally {
            setSending(false);
        }
    };

    const title = t.chat_title ?? 'Order chat';
    const emptyLabel = t.chat_empty ?? 'No messages yet.';
    const placeholder = t.chat_placeholder ?? 'Write a message on behalf of the technician…';
    const sendLabel = sending ? (t.chat_sending ?? 'Sending…') : (t.chat_send ?? 'Send');

    const thread = useMemo(() => messages, [messages]);

    return (
        <div className="card card-outline card-primary">
            <div className="card-header">
                <h3 className="card-title mb-0">
                    <i className={`fas fa-comments ${isRtl ? 'ms-1' : 'me-1'}`} />
                    {title}
                </h3>
            </div>
            <div className="card-body p-0">
                {meta?.has_more_before && (
                    <div className="border-bottom p-2 text-center">
                        <button
                            type="button"
                            className="btn btn-sm btn-outline-secondary"
                            onClick={() => void loadOlder()}
                            disabled={loadingOlder}
                        >
                            {loadingOlder ? '…' : (t.chat_load_older ?? 'Load older messages')}
                        </button>
                    </div>
                )}

                <div
                    className="p-3"
                    style={{ maxHeight: 420, overflowY: 'auto', background: '#f8f9fa' }}
                >
                    {loading && <div className="text-muted small">…</div>}
                    {!loading && thread.length === 0 && (
                        <div className="text-muted small text-center py-4">{emptyLabel}</div>
                    )}
                    {thread.map((m) => {
                        const isCustomer = m.sender_role === 'customer';
                        const bubbleAlign = isCustomer
                            ? (isRtl ? 'flex-start' : 'flex-end')
                            : (isRtl ? 'flex-end' : 'flex-start');
                        const bubbleClass = isCustomer ? 'bg-primary text-white' : 'bg-white border';

                        return (
                            <div
                                key={m.id}
                                className="d-flex mb-2"
                                style={{ justifyContent: bubbleAlign }}
                            >
                                <div
                                    className={`rounded p-2 shadow-sm ${bubbleClass}`}
                                    style={{ maxWidth: '85%' }}
                                >
                                    <div className="small font-weight-bold mb-1">
                                        {m.sender?.display_name || m.sender?.name || m.sender_role_label || m.sender_role}
                                        {m.on_behalf_label ? (
                                            <span className={`badge badge-warning ${isRtl ? 'me-1' : 'ms-1'}`}>
                                                {m.on_behalf_label}
                                            </span>
                                        ) : null}
                                    </div>
                                    <div className="small" style={{ whiteSpace: 'pre-wrap' }}>
                                        {m.body}
                                    </div>
                                    <div
                                        className={`mt-1 ${isCustomer ? 'text-white-50' : 'text-muted'}`}
                                        style={{ fontSize: 11 }}
                                        dir="ltr"
                                    >
                                        {formatTime(m.created_at, locale)}
                                        {m.actor && m.on_behalf_label
                                            ? ` · ${m.actor.display_name || m.actor.name}`
                                            : ''}
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                    <div ref={bottomRef} />
                </div>
            </div>

            <div className="card-footer">
                {error && <div className="alert alert-danger py-2 small mb-2">{error}</div>}
                {canReply ? (
                    <form onSubmit={(e) => void send(e)}>
                        <div className="form-group mb-2">
                            <textarea
                                className="form-control"
                                rows={3}
                                value={body}
                                onChange={(e) => setBody(e.target.value)}
                                placeholder={placeholder}
                                aria-label={placeholder}
                                style={{ textAlign: align }}
                                disabled={sending}
                            />
                        </div>
                        <button type="submit" className="btn btn-primary btn-sm" disabled={sending || !body.trim()}>
                            {sendLabel}
                        </button>
                    </form>
                ) : (
                    <div className="text-muted small mb-0">{t.chat_locked ?? 'Messaging is not available for this order status.'}</div>
                )}
            </div>
        </div>
    );
}
