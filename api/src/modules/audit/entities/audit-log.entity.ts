import {
  Entity, PrimaryGeneratedColumn, Column, CreateDateColumn, Index,
} from 'typeorm';

@Entity('audit_logs')
@Index(['tenantId', 'entityType', 'createdAt'])
@Index(['tenantId', 'userId'])
export class AuditLog {
  @PrimaryGeneratedColumn('increment', { type: 'bigint' })
  id: number;

  @Column({ type: 'uuid' })
  @Index()
  tenantId: string;

  @Column({ nullable: true })
  userId: number;

  @Column({ length: 20 })
  action: string; // 'create', 'update', 'delete', 'login', 'export', 'import'

  @Column({ length: 50 })
  entityType: string; // 'customer', 'contract', 'payment', etc.

  @Column({ nullable: true })
  entityId: number;

  @Column({ type: 'jsonb', nullable: true })
  changes: Record<string, { old: any; new: any }>;

  @Column({ nullable: true, length: 45 })
  ipAddress: string;

  @Column({ nullable: true })
  userAgent: string;

  @CreateDateColumn()
  createdAt: Date;
}
