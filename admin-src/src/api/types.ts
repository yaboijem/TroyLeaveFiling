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
