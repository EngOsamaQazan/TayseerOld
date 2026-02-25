import {
  Entity, PrimaryGeneratedColumn, Column, CreateDateColumn, UpdateDateColumn,
  ManyToOne, JoinColumn, Index,
} from 'typeorm';
import { Tenant } from '../../tenants/entities/tenant.entity';
import { Category } from '../../categories/entities/category.entity';
import { Company } from '../../companies/entities/company.entity';

export enum TransactionType {
  INCOME = 'income',
  EXPENSE = 'expense',
  TRANSFER = 'transfer',
  BANK_IMPORT = 'bank_import',
}

export enum TransactionStatus {
  PENDING = 'pending',
  CONFIRMED = 'confirmed',
  REVERSED = 'reversed',
}

@Entity('financial_transactions')
@Index(['tenantId', 'type'])
@Index(['tenantId', 'contractId'])
@Index(['tenantId', 'date'])
@Index(['tenantId', 'status'])
export class FinancialTransaction {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ type: 'uuid' })
  @Index()
  tenantId: string;

  @ManyToOne(() => Tenant)
  @JoinColumn({ name: 'tenantId' })
  tenant: Tenant;

  @Column({ type: 'enum', enum: TransactionType })
  type: TransactionType;

  @Column({ type: 'enum', enum: TransactionStatus, default: TransactionStatus.CONFIRMED })
  status: TransactionStatus;

  @Column({ type: 'decimal', precision: 15, scale: 2 })
  amount: number;

  @Column({ type: 'date' })
  date: string;

  @Column({ type: 'text', nullable: true })
  description: string;

  // الربط بالعقد
  @Column({ nullable: true })
  contractId: number;

  // الربط بالشركة
  @Column({ nullable: true })
  companyId: number;

  @ManyToOne(() => Company, { nullable: true })
  @JoinColumn({ name: 'companyId' })
  company: Company;

  // الربط بالفئة
  @Column({ nullable: true })
  categoryId: number;

  @ManyToOne(() => Category, { nullable: true })
  @JoinColumn({ name: 'categoryId' })
  category: Category;

  // بيانات الدفع
  @Column({ nullable: true, length: 50 })
  paymentMethod: string;

  @Column({ nullable: true, length: 50 })
  receiptNumber: string;

  @Column({ nullable: true, length: 50 })
  documentNumber: string;

  @Column({ nullable: true, length: 100 })
  bankReference: string;

  // للاستيراد من كشف بنكي
  @Column({ type: 'text', nullable: true })
  bankDescription: string;

  @Column({ nullable: true, length: 50 })
  importBatchId: string;

  // ملاحظات
  @Column({ type: 'text', nullable: true })
  notes: string;

  @Column({ nullable: true })
  createdBy: number;

  @CreateDateColumn()
  createdAt: Date;

  @UpdateDateColumn()
  updatedAt: Date;

  @Column({ default: false })
  isDeleted: boolean;
}
