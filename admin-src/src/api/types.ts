export type WorkArrangement = 'onsite' | 'wfh' | 'hybrid';
export type HomeLocationStatus = 'approved' | 'pending' | 'none';
export type ReviewStatus = 'pending' | 'approved' | 'rejected';

export interface PageMeta {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
  from: number | null;
  to: number | null;
  counts?: HomeLocationCounts;
}

export interface Paginated<T> {
  data: T[];
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
  from: number | null;
  to: number | null;
  next_page_url: string | null;
  prev_page_url: string | null;
}

export type AttendanceType = 'time_in' | 'time_out' | 'break_in' | 'break_out';
export type AttendanceSource = 'app' | 'admin';

export interface AttendanceAdmin {
  id: number;
  uuid: string;
  type: AttendanceType;
  break_kind: string | null;
  expected_end_at: string | null;
  timestamp: string | null;
  is_late: boolean;
  is_early_timeout: boolean;
  work_minutes: number | null;
  break_minutes: number | null;
  is_overbreak: boolean;
  source: AttendanceSource;
  is_offline: boolean;
  notes: string | null;
  employee: {
    id: string;
    employee_id: string;
    name: string;
    department: string | null;
    position: string | null;
  };
  branch: BranchRef | null;
  device: null;
  photo: { path: string } | null;
  gps_location: {
    latitude: number | null;
    longitude: number | null;
    accuracy_meters: number | null;
    distance_from_branch_meters: number | null;
    is_within_radius: boolean | null;
  } | null;
  fraud_flags: {
    id: number;
    type: string;
    severity: string;
    status: string;
  }[];
  created_at: string | null;
}

export interface Department {
  id: string;
  name: string;
}

export interface AppSettings {
  breaks_enabled: boolean;
}

export interface BranchRef {
  id: number;
  name: string;
  code: string;
  is_active?: boolean;
}

export interface Branch {
  id: number;
  name: string;
  code: string;
  address: string | null;
  latitude: number;
  longitude: number;
  radius_meters: number;
  accuracy_ceiling_meters: number;
  accuracy_allowance_meters: number;
  is_active: boolean;
  employee_count: number;
  attendance_count: number;
  created_at: string | null;
  updated_at: string | null;
}

export interface BranchPayload {
  name: string;
  code: string;
  address: string | null;
  latitude: number;
  longitude: number;
  radius_meters: number;
  accuracy_ceiling_meters: number;
  accuracy_allowance_meters: number;
  is_active: boolean;
}

export interface EmployeeRow {
  idno: string;
  name: string;
  email: string;
  department: string | null;
  position: string | null;
  is_active: boolean;
  has_profile: boolean;
  branch: BranchRef | null;
  work_arrangement: WorkArrangement;
  home_location_status: HomeLocationStatus;
}

export interface HomePin {
  id: number;
  label: string | null;
  latitude: number;
  longitude: number;
  radius_meters: number;
  address_text: string | null;
  street: string | null;
  city: string | null;
  province: string | null;
}

export interface HomeLocationReview extends HomePin {
  employee: {
    idno: string;
    name: string;
    email: string;
    department: string | null;
    work_arrangement: WorkArrangement;
    branch: BranchRef | null;
  };
  status: ReviewStatus;
  created_at: string | null;
  reviewed_by: string | null;
  reviewed_at: string | null;
  review_note: string | null;
  current_approved: HomePin | null;
}

export interface HomeLocationCounts {
  pending: number;
  approved: number;
  rejected: number;
}

export interface HomeLocationPage extends Paginated<HomeLocationReview> {
  counts: HomeLocationCounts;
}

export interface Me {
  id: number;
  employee_id: string;
  name: string;
  email: string;
  is_active: boolean;
  roles: string[];
  employee: {
    full_name: string;
    department: string | null;
    position: string | null;
    work_arrangement: string;
    home_location_status: string;
    branch: BranchRef | null;
  } | null;
}

export interface BulkAssignmentPayload {
  idnos: string[];
  branch_id?: number | null;
  work_arrangement?: WorkArrangement | null;
}

export interface BulkAssignmentResult {
  updated: number;
  created: number;
  skipped: number;
  warnings: {
    no_home_pin: number;
    no_branch_for_onsite: number;
  };
}
