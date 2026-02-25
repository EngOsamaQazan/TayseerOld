import { Injectable, NotFoundException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { FinancialTransaction, TransactionType, TransactionStatus } from './entities/financial-transaction.entity';
import { CreateFinancialTransactionDto, QueryTransactionsDto } from './dto/create-financial-transaction.dto';

@Injectable()
export class FinancialTransactionsService {
  constructor(
    @InjectRepository(FinancialTransaction)
    private readonly txRepo: Repository<FinancialTransaction>,
  ) {}

  async create(dto: CreateFinancialTransactionDto, tenantId: string, userId: number): Promise<FinancialTransaction> {
    const tx = this.txRepo.create({ ...dto, tenantId, createdBy: userId });
    return this.txRepo.save(tx);
  }

  async findAll(
    tenantId: string,
    query: QueryTransactionsDto,
    page = 1,
    limit = 20,
  ): Promise<{ data: FinancialTransaction[]; total: number; summary: any }> {
    const qb = this.txRepo.createQueryBuilder('ft')
      .leftJoinAndSelect('ft.category', 'cat')
      .leftJoinAndSelect('ft.company', 'comp')
      .where('ft.tenantId = :tenantId', { tenantId })
      .andWhere('ft.isDeleted = false');

    if (query.type) qb.andWhere('ft.type = :type', { type: query.type });
    if (query.status) qb.andWhere('ft.status = :status', { status: query.status });
    if (query.contractId) qb.andWhere('ft.contractId = :contractId', { contractId: query.contractId });
    if (query.companyId) qb.andWhere('ft.companyId = :companyId', { companyId: query.companyId });
    if (query.categoryId) qb.andWhere('ft.categoryId = :categoryId', { categoryId: query.categoryId });
    if (query.dateFrom) qb.andWhere('ft.date >= :dateFrom', { dateFrom: query.dateFrom });
    if (query.dateTo) qb.andWhere('ft.date <= :dateTo', { dateTo: query.dateTo });

    const total = await qb.getCount();
    const data = await qb
      .orderBy('ft.date', 'DESC')
      .addOrderBy('ft.id', 'DESC')
      .skip((page - 1) * limit)
      .take(limit)
      .getMany();

    // حساب الملخص
    const summaryQb = this.txRepo.createQueryBuilder('ft')
      .select('ft.type', 'type')
      .addSelect('SUM(ft.amount)', 'total')
      .addSelect('COUNT(*)', 'count')
      .where('ft.tenantId = :tenantId', { tenantId })
      .andWhere('ft.isDeleted = false')
      .andWhere('ft.status = :status', { status: TransactionStatus.CONFIRMED });

    if (query.dateFrom) summaryQb.andWhere('ft.date >= :dateFrom', { dateFrom: query.dateFrom });
    if (query.dateTo) summaryQb.andWhere('ft.date <= :dateTo', { dateTo: query.dateTo });
    if (query.companyId) summaryQb.andWhere('ft.companyId = :companyId', { companyId: query.companyId });

    const summaryRows = await summaryQb.groupBy('ft.type').getRawMany();

    const summary = {
      totalIncome: 0, totalExpense: 0, netBalance: 0,
      incomeCount: 0, expenseCount: 0,
    };
    for (const row of summaryRows) {
      if (row.type === TransactionType.INCOME) {
        summary.totalIncome = parseFloat(row.total) || 0;
        summary.incomeCount = parseInt(row.count) || 0;
      } else if (row.type === TransactionType.EXPENSE) {
        summary.totalExpense = parseFloat(row.total) || 0;
        summary.expenseCount = parseInt(row.count) || 0;
      }
    }
    summary.netBalance = summary.totalIncome - summary.totalExpense;

    return { data, total, summary };
  }

  async findOne(id: number, tenantId: string): Promise<FinancialTransaction> {
    const tx = await this.txRepo.findOne({
      where: { id, tenantId, isDeleted: false },
      relations: ['category', 'company'],
    });
    if (!tx) throw new NotFoundException('الحركة المالية غير موجودة');
    return tx;
  }

  async update(id: number, dto: Partial<CreateFinancialTransactionDto>, tenantId: string): Promise<FinancialTransaction> {
    const tx = await this.findOne(id, tenantId);
    Object.assign(tx, dto);
    return this.txRepo.save(tx);
  }

  async reverse(id: number, tenantId: string): Promise<FinancialTransaction> {
    const tx = await this.findOne(id, tenantId);
    tx.status = TransactionStatus.REVERSED;
    return this.txRepo.save(tx);
  }

  async softDelete(id: number, tenantId: string): Promise<void> {
    const tx = await this.findOne(id, tenantId);
    tx.isDeleted = true;
    await this.txRepo.save(tx);
  }

  async classifyBankImport(id: number, dto: { type: TransactionType; categoryId?: number; contractId?: number }, tenantId: string): Promise<FinancialTransaction> {
    const tx = await this.findOne(id, tenantId);
    if (tx.type !== TransactionType.BANK_IMPORT) {
      throw new NotFoundException('هذه العملية متاحة فقط للحركات المستوردة من كشف بنكي');
    }
    tx.type = dto.type;
    tx.categoryId = dto.categoryId as any;
    tx.contractId = dto.contractId as any;
    tx.status = TransactionStatus.CONFIRMED;
    return this.txRepo.save(tx);
  }

  async getContractBalance(contractId: number, tenantId: string): Promise<{ totalPaid: number; totalExpenses: number }> {
    const result = await this.txRepo.createQueryBuilder('ft')
      .select('ft.type', 'type')
      .addSelect('SUM(ft.amount)', 'total')
      .where('ft.tenantId = :tenantId', { tenantId })
      .andWhere('ft.contractId = :contractId', { contractId })
      .andWhere('ft.isDeleted = false')
      .andWhere('ft.status = :status', { status: TransactionStatus.CONFIRMED })
      .groupBy('ft.type')
      .getRawMany();

    let totalPaid = 0, totalExpenses = 0;
    for (const row of result) {
      if (row.type === TransactionType.INCOME) totalPaid = parseFloat(row.total) || 0;
      if (row.type === TransactionType.EXPENSE) totalExpenses = parseFloat(row.total) || 0;
    }
    return { totalPaid, totalExpenses };
  }
}
