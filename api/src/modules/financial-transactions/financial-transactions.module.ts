import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { FinancialTransaction } from './entities/financial-transaction.entity';
import { FinancialTransactionsService } from './financial-transactions.service';
import { FinancialTransactionsController } from './financial-transactions.controller';

@Module({
  imports: [TypeOrmModule.forFeature([FinancialTransaction])],
  providers: [FinancialTransactionsService],
  controllers: [FinancialTransactionsController],
  exports: [FinancialTransactionsService],
})
export class FinancialTransactionsModule {}
