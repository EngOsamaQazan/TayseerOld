import {
  Entity, PrimaryGeneratedColumn, Column, CreateDateColumn, UpdateDateColumn,
  ManyToOne, JoinColumn, Index, Unique,
} from 'typeorm';
import { Tenant } from '../../tenants/entities/tenant.entity';

@Entity('lookups')
@Unique(['tenantId', 'type', 'name'])
@Index(['tenantId', 'type'])
export class Lookup {
  @PrimaryGeneratedColumn()
  id: number;

  @Column({ type: 'uuid' })
  @Index()
  tenantId: string;

  @ManyToOne(() => Tenant)
  @JoinColumn({ name: 'tenantId' })
  tenant: Tenant;

  @Column({ length: 50 })
  type: string;

  @Column({ length: 255 })
  name: string;

  @Column({ nullable: true, length: 255 })
  nameEn: string;

  @Column({ nullable: true })
  parentId: number;

  @ManyToOne(() => Lookup, { nullable: true })
  @JoinColumn({ name: 'parentId' })
  parent: Lookup;

  @Column({ default: 0 })
  sortOrder: number;

  @Column({ default: true })
  isActive: boolean;

  @Column({ type: 'jsonb', nullable: true })
  metadata: Record<string, any>;

  @CreateDateColumn()
  createdAt: Date;

  @UpdateDateColumn()
  updatedAt: Date;
}
