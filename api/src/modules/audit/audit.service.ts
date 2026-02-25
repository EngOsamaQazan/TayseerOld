import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { AuditLog } from './entities/audit-log.entity';

@Injectable()
export class AuditService {
  constructor(
    @InjectRepository(AuditLog)
    private readonly auditRepo: Repository<AuditLog>,
  ) {}

  async log(params: {
    tenantId: string;
    userId?: number;
    action: string;
    entityType: string;
    entityId?: number;
    changes?: Record<string, { old: any; new: any }>;
    ipAddress?: string;
  }): Promise<void> {
    await this.auditRepo.save(this.auditRepo.create(params));
  }

  async findByEntity(
    tenantId: string,
    entityType: string,
    entityId: number,
  ): Promise<AuditLog[]> {
    return this.auditRepo.find({
      where: { tenantId, entityType, entityId },
      order: { createdAt: 'DESC' },
      take: 50,
    });
  }

  async findByUser(tenantId: string, userId: number, page = 1, limit = 50): Promise<{ data: AuditLog[]; total: number }> {
    const [data, total] = await this.auditRepo.findAndCount({
      where: { tenantId, userId },
      order: { createdAt: 'DESC' },
      skip: (page - 1) * limit,
      take: limit,
    });
    return { data, total };
  }

  async findRecent(tenantId: string, limit = 20): Promise<AuditLog[]> {
    return this.auditRepo.find({
      where: { tenantId },
      order: { createdAt: 'DESC' },
      take: limit,
    });
  }
}
