export interface CPAItem {
  id: string;
  category: string;
  title: string;
  description: string;
  condition: string;
  reward: number;
  approvalRate: string;
  badge: '진행중' | '인기' | '신규' | '고수익' | '승인율 높음';
  imageUrl?: string;
}

export interface CPSItem {
  id: string;
  category: string;
  brand: string;
  description: string;
  condition: string;
  rewardRate: string;
  cookieDays: number;
  badge?: '인기' | '신규' | '고수익률' | '수익률 상향';
  imageUrl?: string;
}
