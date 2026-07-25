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