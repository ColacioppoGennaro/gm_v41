export enum EventStatus {
  Pending = 'pending',
  Completed = 'completed',
}

export type Recurrence = 'none' | 'daily' | 'weekly' | 'monthly' | 'yearly';

export interface Category {
  id: string;
  name: string;
  color: string;
  icon: string;
  event_count?: number;
  is_default?: boolean;
}

export interface Event {
  id: string;
  title: string;
  start_datetime: string;
  end_datetime?: string;
  amount?: number;
  category_id: string;
  category?: Category;
  category_name?: string;
  category_color?: string;
  category_icon?: string;
  status: EventStatus;
  has_document: boolean;
  reminders: number[];
  source?: 'local' | 'google';
  description?: string;
  recurrence?: Recurrence;
  recurrence_pattern?: string | null;
  color?: string;
  google_event_id?: string | null;
}

export interface User {
  id: number;
  email: string;
  plan: 'free' | 'pro';
  ai_queries_count?: number;
  ai_queries_limit?: number;
  storage_used_mb?: number;
  storage_limit_mb?: number;
  google_calendar_connected?: boolean;
  created_at?: string;
  last_login_at?: string;
}

export interface Document {
  id: string;
  filename: string;
  upload_date: string;
  ai_summary?: string;
  extracted_amount?: number;
  event_id?: string;
  event?: {
    title: string;
  };
}

export interface AuthResponse {
  user: User;
  token: string;
}
