import { Controller, Get, Post, Put, Delete, Body, Param, Query, UseGuards, Request, ParseIntPipe } from '@nestjs/common';
import { ApiBearerAuth, ApiTags, ApiOperation, ApiQuery } from '@nestjs/swagger';
import { FinancialTransactionsService } from './financial-transactions.service';
import { CreateFinancialTransactionDto, QueryTransactionsDto } from './dto/create-financial-transaction.dto';
import { TransactionType } from './entities/financial-transaction.entity';
import { JwtAuthGuard } from '../auth/guards/jwt-auth.guard';

@ApiTags('Financial Transactions - الحركات المالية')
@ApiBearerAuth()
@UseGuards(JwtAuthGuard)
@Controller('financial-transactions')
export class FinancialTransactionsController {
  constructor(private readonly ftService: FinancialTransactionsService) {}

  @Post()
  @ApiOperation({ summary: 'إنشاء حركة مالية (دخل/مصاريف/تحويل)' })
  create(@Body() dto: CreateFinancialTransactionDto, @Request() req: any) {
    return this.ftService.create(dto, req.user.tenantId, req.user.id);
  }

  @Get()
  @ApiOperation({ summary: 'قائمة الحركات المالية مع ملخص' })
  @ApiQuery({ name: 'page', required: false })
  @ApiQuery({ name: 'limit', required: false })
  @ApiQuery({ name: 'type', required: false, enum: TransactionType })
  @ApiQuery({ name: 'dateFrom', required: false })
  @ApiQuery({ name: 'dateTo', required: false })
  @ApiQuery({ name: 'contractId', required: false })
  @ApiQuery({ name: 'companyId', required: false })
  findAll(
    @Query('page') page = 1,
    @Query('limit') limit = 20,
    @Query() query: QueryTransactionsDto,
    @Request() req: any,
  ) {
    return this.ftService.findAll(req.user.tenantId, query, +page, +limit);
  }

  @Get('contract/:contractId/balance')
  @ApiOperation({ summary: 'رصيد عقد (إجمالي المدفوع والمصاريف)' })
  getContractBalance(@Param('contractId', ParseIntPipe) contractId: number, @Request() req: any) {
    return this.ftService.getContractBalance(contractId, req.user.tenantId);
  }

  @Get(':id')
  @ApiOperation({ summary: 'عرض حركة مالية' })
  findOne(@Param('id', ParseIntPipe) id: number, @Request() req: any) {
    return this.ftService.findOne(id, req.user.tenantId);
  }

  @Put(':id')
  @ApiOperation({ summary: 'تعديل حركة مالية' })
  update(@Param('id', ParseIntPipe) id: number, @Body() dto: Partial<CreateFinancialTransactionDto>, @Request() req: any) {
    return this.ftService.update(id, dto, req.user.tenantId);
  }

  @Put(':id/classify')
  @ApiOperation({ summary: 'تصنيف حركة مستوردة من كشف بنكي' })
  classify(@Param('id', ParseIntPipe) id: number, @Body() dto: { type: TransactionType; categoryId?: number; contractId?: number }, @Request() req: any) {
    return this.ftService.classifyBankImport(id, dto, req.user.tenantId);
  }

  @Put(':id/reverse')
  @ApiOperation({ summary: 'عكس حركة مالية' })
  reverse(@Param('id', ParseIntPipe) id: number, @Request() req: any) {
    return this.ftService.reverse(id, req.user.tenantId);
  }

  @Delete(':id')
  @ApiOperation({ summary: 'حذف حركة مالية' })
  remove(@Param('id', ParseIntPipe) id: number, @Request() req: any) {
    return this.ftService.softDelete(id, req.user.tenantId);
  }
}
